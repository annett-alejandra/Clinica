<?php

namespace Tests\Feature;

use Illuminate\Database\PostgresConnection;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use LogicException;
use Tests\TestCase;

class ChatbotIATest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:' . base64_encode(random_bytes(32)),
            'session.driver' => 'array',
            'cache.default' => 'array',
            'logging.default' => 'null',
            'database.default' => 'blocked',
            'database.connections.blocked' => ['driver' => 'blocked'],
            'services.ia_chatbot' => [
                'provider' => 'auto',
                'gemini_key' => null,
                'gemini_model' => 'gemini-2.0-flash',
                'groq_key' => null,
                'groq_model' => 'llama-3.3-70b-versatile',
                'openai_key' => null,
                'openai_model' => 'gpt-4o-mini',
                'openrouter_key' => null,
                'openrouter_model' => 'meta-llama/llama-3.3-70b-instruct:free',
                'timeout' => 15,
            ],
        ]);

        DB::extend('blocked', fn () => new PostgresConnection(
            fn () => throw new LogicException('Las pruebas del chatbot no requieren base de datos activa.'),
            'unused',
        ));
    }

    public function test_asistente_ia_view_renders_cleanly_with_degraded_database(): void
    {
        $this->get(route('asistente-ia'))
            ->assertOk()
            ->assertViewIs('Paquetes.P8_AsistenciaInteligente.CU20_AsistenteVirtualIA.index')
            ->assertSee('Asistente Virtual Clínico IA')
            ->assertSee('chatbotClinica')
            ->assertDontSee('Configuración Motor IA (Admin)');

        $this->getJson('/api/chatbot/estado')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.configurado', false)
            ->assertJsonPath('data.modo', 'local_fallback')
            ->assertJsonPath('data.proveedor', 'asistente_clinico_local');
    }

    public function test_chatbot_responds_with_local_fallback_when_no_external_api_key_is_configured(): void
    {
        $response = $this->postJson('/api/chatbot/mensaje', [
            'mensaje' => '¿Qué especialidades atienden en la clínica?',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.modo', 'local_fallback')
            ->assertJsonPath('data.proveedor', 'asistente_clinico_local')
            ->assertJsonPath('data.fallback_activado', false)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['respuesta', 'proveedor', 'modelo', 'modo', 'fallback_activado', 'enlaces', 'timestamp'],
            ]);

        $this->assertStringContainsString('Cardiología', (string) $response->json('data.respuesta'));
    }

    public function test_chatbot_calls_gemini_api_when_gemini_key_is_configured(): void
    {
        config([
            'services.ia_chatbot.provider' => 'gemini',
            'services.ia_chatbot.gemini_key' => 'test-gemini-secret-key',
            'services.ia_chatbot.gemini_model' => 'gemini-2.0-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'En Clínica Vida Salud atendemos Cardiología, Pediatría y Medicina Interna.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/chatbot/mensaje', [
            'mensaje' => '¿Qué especialidades tienen?',
            'historial' => [
                ['rol' => 'user', 'contenido' => 'Hola'],
                ['rol' => 'assistant', 'contenido' => '¡Hola! ¿En qué puedo ayudarte?'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.modo', 'ia_api')
            ->assertJsonPath('data.proveedor', 'gemini')
            ->assertJsonPath('data.modelo', 'gemini-2.0-flash')
            ->assertJsonPath('data.fallback_activado', false)
            ->assertJsonPath('data.respuesta', 'En Clínica Vida Salud atendemos Cardiología, Pediatría y Medicina Interna.');

        Http::assertSent(function (HttpClientRequest $request): bool {
            $body = $request->data();
            $system = $body['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($request->url(), 'generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-gemini-secret-key')
                && str_contains($system, 'Clínica Vida Salud')
                && count($body['contents'] ?? []) === 3;
        });
    }

    public function test_chatbot_calls_openai_compatible_api_via_web_endpoint(): void
    {
        config([
            'services.ia_chatbot.provider' => 'openai',
            'services.ia_chatbot.openai_key' => 'test-openai-secret-key',
            'services.ia_chatbot.openai_model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Puedes agendar o consultar horarios en el módulo de Disponibilidad (/disponibilidad).',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $token = str_repeat('a', 40);
        $response = $this->withSession(['_token' => $token])
            ->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('asistente-ia.mensaje'), [
                'mensaje' => '¿Cómo agendo una cita médica?',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.modo', 'ia_api')
            ->assertJsonPath('data.proveedor', 'openai')
            ->assertJsonPath('data.modelo', 'gpt-4o-mini')
            ->assertJsonPath('data.fallback_activado', false)
            ->assertJsonPath('data.respuesta', 'Puedes agendar o consultar horarios en el módulo de Disponibilidad (/disponibilidad).');

        Http::assertSent(fn (HttpClientRequest $request): bool =>
            str_contains($request->url(), 'api.openai.com/v1/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer test-openai-secret-key')
        );
    }

    public function test_chatbot_falls_back_gracefully_when_external_ai_api_returns_500(): void
    {
        config([
            'services.ia_chatbot.provider' => 'gemini',
            'services.ia_chatbot.gemini_key' => 'test-gemini-secret-key',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Internal Server Error'],
            ], 500),
        ]);

        $response = $this->postJson('/api/chatbot/mensaje', [
            'mensaje' => '¿Dónde está ubicada la clínica y cuál es su teléfono de urgencias?',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.modo', 'local_fallback')
            ->assertJsonPath('data.proveedor', 'asistente_clinico_local')
            ->assertJsonPath('data.fallback_activado', true);

        $this->assertNotEmpty($response->json('data.respuesta'));
    }

    public function test_chatbot_validates_empty_and_oversized_messages_with_422(): void
    {
        $this->postJson('/api/chatbot/mensaje', [
            'mensaje' => '   ',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['mensaje']]);

        $this->postJson('/api/chatbot/mensaje', [
            'mensaje' => str_repeat('x', 1001),
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['mensaje']]);
    }
}
