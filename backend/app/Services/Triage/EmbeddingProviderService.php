<?php

namespace App\Services\Triage;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pasarela híbrida de Embeddings y LLM.
 * Intenta utilizar el proveedor configurado (OpenAI, Gemini o Mistral) si cuenta con API key
 * y realiza fallback automático transparente al motor vectorial local TF-IDF/clínico.
 */
class EmbeddingProviderService
{
    public function __construct(protected VectorMathService $vectorMath) {}

    /**
     * Determina el proveedor activo según configuración y disponibilidad de API keys.
     */
    public function proveedorActivo(): string
    {
        $modo = strtolower((string) config('triage.provider', 'auto'));

        if ($modo === 'local') {
            return 'local_tfidf';
        }

        if (in_array($modo, ['groq', 'openai', 'gemini', 'mistral'], true)) {
            $key = (string) config("triage.providers.{$modo}.api_key", '');

            return $key !== '' ? $modo : 'local_tfidf';
        }

        // Modo 'auto': prioriza Groq/Gemini (compartido con CU20) y luego OpenAI / Mistral
        foreach (['groq', 'gemini', 'openai', 'mistral'] as $candidato) {
            if ((string) config("triage.providers.{$candidato}.api_key", '') !== '') {
                return $candidato;
            }
        }

        return 'local_tfidf';
    }

    /**
     * Genera un embedding semántico (denso si hay API externa, disperso local en fallback).
     *
     * @return array{vector: array, provider: string, tipo: string}
     */
    public function generarEmbedding(string $texto, array $etiquetas = []): array
    {
        $proveedor = $this->proveedorActivo();

        if ($proveedor !== 'local_tfidf') {
            $vectorExterno = match ($proveedor) {
                'openai' => $this->embeddingOpenAi($texto),
                'gemini' => $this->embeddingGemini($texto),
                'mistral' => $this->embeddingMistral($texto),
                default => null,
            };

            if (is_array($vectorExterno) && $vectorExterno !== []) {
                return [
                    'vector' => $this->vectorMath->normalizarL2($vectorExterno),
                    'provider' => $proveedor,
                    'tipo' => 'dense',
                ];
            }
        }

        return [
            'vector' => $this->vectorMath->construirVectorLocal($texto, $etiquetas),
            'provider' => 'local_tfidf',
            'tipo' => 'sparse',
        ];
    }

    /**
     * Opcionalmente solicita al LLM configurado una explicación clara y empática para el paciente.
     * Si no hay API key o falla la red, retorna null para que el motor use la explicación estructurada local.
     */
    public function generarExplicacionLlm(string $sintomas, array $topCandidato, array $evaluacionEmergencia): ?string
    {
        if (! config('triage.use_llm_explanation', true)) {
            return null;
        }

        $proveedor = $this->proveedorActivo();
        if ($proveedor === 'local_tfidf') {
            return null;
        }

        $sistema = 'Eres un asistente de orientación clínica preliminar (triage) de Clínica Vida Salud. '
            .'Explica en 2 a 3 frases claras, empáticas y en español sencillo por qué se sugiere la especialidad y el médico recomendado. '
            .'Nunca emitas un diagnóstico definitivo ni recetes medicamentos.';

        $usuario = sprintf(
            "Síntomas reportados por el paciente: \"%s\"\nEspecialidad recomendada: %s (%s%% de afinidad)\nMédico sugerido: %s\nSubespecialidades: %s\nNivel de urgencia: %s",
            $sintomas,
            $topCandidato['especialidad']['nombre_especialidad'] ?? 'Medicina Interna',
            $topCandidato['porcentaje_afinidad'] ?? 0,
            $topCandidato['medico']['nombre_completo'] ?? 'Especialista',
            implode(', ', $topCandidato['subespecialidades'] ?? []),
            $evaluacionEmergencia['nivel_urgencia'] ?? 'NORMAL'
        );

        try {
            return match ($proveedor) {
                'groq' => $this->chatGroq($sistema, $usuario),
                'openai' => $this->chatOpenAi($sistema, $usuario),
                'gemini' => $this->chatGemini($sistema, $usuario),
                'mistral' => $this->chatMistral($sistema, $usuario),
                default => null,
            };
        } catch (Throwable $e) {
            Log::warning("Triage IA ({$proveedor}): fallback a explicación local. {$e->getMessage()}");

            return null;
        }
    }

    private function embeddingOpenAi(string $texto): ?array
    {
        try {
            $cfg = config('triage.providers.openai');
            $resp = Http::timeout((int) config('triage.http_timeout', 8))
                ->withToken((string) $cfg['api_key'])
                ->acceptJson()
                ->post(rtrim((string) $cfg['base_url'], '/').'/embeddings', [
                    'model' => $cfg['embedding_model'],
                    'input' => mb_substr($texto, 0, 4000),
                ]);

            if ($resp->successful()) {
                $vec = $resp->json('data.0.embedding');

                return is_array($vec) ? $vec : null;
            }
        } catch (Throwable $e) {
            Log::warning('OpenAI embedding falló, usando fallback local: '.$e->getMessage());
        }

        return null;
    }

    private function embeddingGemini(string $texto): ?array
    {
        try {
            $cfg = config('triage.providers.gemini');
            $model = (string) $cfg['embedding_model'];
            $url = rtrim((string) $cfg['base_url'], '/').'/models/'.urlencode($model).':embedContent';

            $resp = Http::timeout((int) config('triage.http_timeout', 12))
                ->withHeaders([
                    'x-goog-api-key' => (string) $cfg['api_key'],
                ])
                ->acceptJson()
                ->post($url, [
                    'model' => "models/{$model}",
                    'content' => [
                        'parts' => [['text' => mb_substr($texto, 0, 4000)]],
                    ],
                ]);

            if ($resp->successful()) {
                $vec = $resp->json('embedding.values');

                return is_array($vec) ? $vec : null;
            }

            Log::warning('Gemini embedding HTTP '.$resp->status().' — usando fallback vectorial local.', [
                'body' => substr($resp->body(), 0, 220),
            ]);
        } catch (Throwable $e) {
            Log::warning('Gemini embedding falló, usando fallback local: '.$e->getMessage());
        }

        return null;
    }

    private function embeddingMistral(string $texto): ?array
    {
        try {
            $cfg = config('triage.providers.mistral');
            $resp = Http::timeout((int) config('triage.http_timeout', 8))
                ->withToken((string) $cfg['api_key'])
                ->acceptJson()
                ->post(rtrim((string) $cfg['base_url'], '/').'/embeddings', [
                    'model' => $cfg['embedding_model'],
                    'input' => [mb_substr($texto, 0, 4000)],
                ]);

            if ($resp->successful()) {
                $vec = $resp->json('data.0.embedding');

                return is_array($vec) ? $vec : null;
            }
        } catch (Throwable $e) {
            Log::warning('Mistral embedding falló, usando fallback local: '.$e->getMessage());
        }

        return null;
    }

    private function chatGroq(string $sistema, string $usuario): ?string
    {
        $cfg = config('triage.providers.groq');
        $resp = Http::timeout((int) config('triage.http_timeout', 12))
            ->withToken((string) $cfg['api_key'])
            ->acceptJson()
            ->post(rtrim((string) $cfg['base_url'], '/').'/chat/completions', [
                'model' => $cfg['chat_model'],
                'temperature' => 0.3,
                'max_tokens' => 260,
                'messages' => [
                    ['role' => 'system', 'content' => $sistema],
                    ['role' => 'user', 'content' => $usuario],
                ],
            ]);

        if (! $resp->successful()) {
            Log::warning('Groq chat triage HTTP '.$resp->status().' — usando explicación clínica local.', [
                'body' => substr($resp->body(), 0, 220),
            ]);

            return null;
        }

        $texto = trim((string) $resp->json('choices.0.message.content', ''));

        return $texto !== '' ? $texto : null;
    }

    private function chatOpenAi(string $sistema, string $usuario): ?string
    {
        $cfg = config('triage.providers.openai');
        $resp = Http::timeout((int) config('triage.http_timeout', 8))
            ->withToken((string) $cfg['api_key'])
            ->acceptJson()
            ->post(rtrim((string) $cfg['base_url'], '/').'/chat/completions', [
                'model' => $cfg['chat_model'],
                'temperature' => 0.3,
                'max_tokens' => 220,
                'messages' => [
                    ['role' => 'system', 'content' => $sistema],
                    ['role' => 'user', 'content' => $usuario],
                ],
            ]);

        $texto = $resp->successful() ? trim((string) $resp->json('choices.0.message.content', '')) : '';

        return $texto !== '' ? $texto : null;
    }

    private function chatGemini(string $sistema, string $usuario): ?string
    {
        $cfg = config('triage.providers.gemini');
        $model = (string) $cfg['chat_model'];
        $url = rtrim((string) $cfg['base_url'], '/').'/models/'.urlencode($model).':generateContent';

        $resp = Http::timeout((int) config('triage.http_timeout', 12))
            ->withHeaders([
                'x-goog-api-key' => (string) $cfg['api_key'],
            ])
            ->acceptJson()
            ->post($url, [
                'systemInstruction' => ['parts' => [['text' => $sistema]]],
                'contents' => [['parts' => [['text' => $usuario]]]],
                'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 260],
            ]);

        if (! $resp->successful()) {
            Log::warning('Gemini chat triage HTTP '.$resp->status().' — usando explicación clínica local.', [
                'body' => substr($resp->body(), 0, 220),
            ]);

            return null;
        }

        $texto = trim((string) $resp->json('candidates.0.content.parts.0.text', ''));

        return $texto !== '' ? $texto : null;
    }

    private function chatMistral(string $sistema, string $usuario): ?string
    {
        $cfg = config('triage.providers.mistral');
        $resp = Http::timeout((int) config('triage.http_timeout', 8))
            ->withToken((string) $cfg['api_key'])
            ->acceptJson()
            ->post(rtrim((string) $cfg['base_url'], '/').'/chat/completions', [
                'model' => $cfg['chat_model'],
                'temperature' => 0.3,
                'max_tokens' => 220,
                'messages' => [
                    ['role' => 'system', 'content' => $sistema],
                    ['role' => 'user', 'content' => $usuario],
                ],
            ]);

        $texto = $resp->successful() ? trim((string) $resp->json('choices.0.message.content', '')) : '';

        return $texto !== '' ? $texto : null;
    }
}
