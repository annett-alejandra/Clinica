<?php

namespace App\Services\Triage;

/**
 * Detector clínico de banderas rojas (Código Rojo / Emergencias) y prioridad alta.
 * Evalúa frases críticas exactas y combinaciones sintomáticas de riesgo vital
 * para disparar protocolos de urgencia al paciente y alertas rojas al Administrador.
 */
class EmergencyDetectorService
{
    public function __construct(protected VectorMathService $vectorMath) {}

    /**
     * @return array{
     *     es_emergencia: bool,
     *     nivel_urgencia: string,
     *     palabras_detectadas: list<string>,
     *     motivos_alerta: list<string>,
     *     protocolo: array<string, mixed>|null
     * }
     */
    public function evaluar(string $sintomasTexto): array
    {
        $normalizado = $this->vectorMath->normalizarTexto($sintomasTexto);
        $reglas = config('triage.reglas_emergencia', []);

        $palabrasDetectadas = [];
        $motivosAlerta = [];
        $tipoProtocolo = 'GENERAL';

        // 1. Verificar frases críticas directas
        foreach ($reglas['frases_criticas'] ?? [] as $frase) {
            $fraseNorm = $this->vectorMath->normalizarTexto((string) $frase);
            if ($fraseNorm !== '' && str_contains($normalizado, $fraseNorm)) {
                $palabrasDetectadas[] = $frase;
                $motivosAlerta[] = "Señal de alarma detectada: \"{$frase}\"";

                if (str_contains($fraseNorm, 'pecho') || str_contains($fraseNorm, 'respirar') || str_contains($fraseNorm, 'infarto') || str_contains($fraseNorm, 'ahogo')) {
                    $tipoProtocolo = 'CARDIOPULMONAR';
                } elseif (str_contains($fraseNorm, 'hablar') || str_contains($fraseNorm, 'paralisis') || str_contains($fraseNorm, 'convulsion') || str_contains($fraseNorm, 'derrame') || str_contains($fraseNorm, 'acv')) {
                    $tipoProtocolo = 'NEUROLOGICO';
                } elseif (str_contains($fraseNorm, 'embarazo')) {
                    $tipoProtocolo = 'OBSTETRICO';
                }
            }
        }

        // 2. Verificar combinaciones sintomáticas críticas (Grupo A + Grupo B)
        foreach ($reglas['combinaciones_criticas'] ?? [] as $combo) {
            $hitA = $this->encontrarCoincidencia($normalizado, $combo['grupo_a'] ?? []);
            $hitB = $this->encontrarCoincidencia($normalizado, $combo['grupo_b'] ?? []);

            if ($hitA !== null && $hitB !== null) {
                $palabrasDetectadas[] = "{$hitA} + {$hitB}";
                $motivosAlerta[] = (string) ($combo['etiqueta'] ?? 'Combinación clínica de alto riesgo');
                $tipoProtocolo = (string) ($combo['protocolo'] ?? $tipoProtocolo);
            }
        }

        $palabrasDetectadas = array_values(array_unique($palabrasDetectadas));
        $motivosAlerta = array_values(array_unique($motivosAlerta));

        if ($palabrasDetectadas !== []) {
            return [
                'es_emergencia' => true,
                'nivel_urgencia' => 'EMERGENCIA',
                'palabras_detectadas' => $palabrasDetectadas,
                'motivos_alerta' => $motivosAlerta,
                'protocolo' => $this->construirProtocoloEmergencia($tipoProtocolo),
            ];
        }

        // 3. Verificar indicadores de atención prioritaria (Código Amarillo)
        $prioritarias = [];
        foreach ($reglas['frases_prioritarias'] ?? [] as $frasePrio) {
            $fraseNorm = $this->vectorMath->normalizarTexto((string) $frasePrio);
            if ($fraseNorm !== '' && str_contains($normalizado, $fraseNorm)) {
                $prioritarias[] = $frasePrio;
            }
        }

        if ($prioritarias !== []) {
            return [
                'es_emergencia' => false,
                'nivel_urgencia' => 'PRIORITARIO',
                'palabras_detectadas' => array_values(array_unique($prioritarias)),
                'motivos_alerta' => ['Se recomienda evaluación médica prioritaria dentro de las próximas 24 horas.'],
                'protocolo' => null,
            ];
        }

        return [
            'es_emergencia' => false,
            'nivel_urgencia' => 'NORMAL',
            'palabras_detectadas' => [],
            'motivos_alerta' => [],
            'protocolo' => null,
        ];
    }

    private function encontrarCoincidencia(string $textoNormalizado, array $terminos): ?string
    {
        foreach ($terminos as $termino) {
            $norm = $this->vectorMath->normalizarTexto((string) $termino);
            if ($norm !== '' && str_contains($textoNormalizado, $norm)) {
                return (string) $termino;
            }
        }

        return null;
    }

    private function construirProtocoloEmergencia(string $tipoProtocolo): array
    {
        $cfgEmergencia = config('triage.emergencia', []);

        $pasosPorTipo = match ($tipoProtocolo) {
            'CARDIOPULMONAR' => [
                'Detenga cualquier esfuerzo físico inmediatamente y permanezca sentado o semi-incorporado.',
                'No conduzca por su cuenta: solicite traslado de emergencia o acompañante inmediato.',
                'Afloje prendas ajustadas en cuello o tórax y mantenga la calma respirando lento.',
                'Acuda de inmediato a la Sala de Urgencias 24/7 para electrocardiograma y evaluación cardiopulmonar prioritaria.',
            ],
            'NEUROLOGICO' => [
                'Registre la hora exacta en que iniciaron los síntomas (crítico para ventana terapéutica de ACV).',
                'No ingiera alimentos, líquidos ni medicamentos por vía oral mientras llega asistencia.',
                'Mantenga al paciente recostado de costado en posición segura si hay somnolencia o convulsión.',
                'Active traslado de emergencia inmediato hacia Urgencias Neurológicas 24/7.',
            ],
            'OBSTETRICO' => [
                'Guarde reposo inmediato recostada preferentemente sobre el lado izquierdo.',
                'No se automedique ni espere a una consulta programada ordinaria.',
                'Tenga a mano su carnet prenatal y acuda de inmediato a Urgencias Obstétricas / Maternidad 24/7.',
            ],
            default => [
                'No espere a una cita ambulatoria regular: sus síntomas requieren evaluación clínica inmediata.',
                'Comuníquese ahora mismo con Urgencias 24/7 o con el servicio de ambulancias.',
                'Permanezca acompañado mientras recibe asistencia médica y tenga a mano su documento de identidad.',
                'Dirección Clínica y Recepción de Clínica Vida Salud han recibido una Alerta Roja de Triage en tiempo real.',
            ],
        };

        return [
            'codigo' => 'CODIGO_ROJO',
            'tipo' => $tipoProtocolo,
            'titulo' => 'PROTOCOLO DE URGENCIA INMEDIATA ACTIVADO',
            'subtitulo' => 'Sus síntomas presentan señales de alarma clínica. Se notificó una Alerta Roja al equipo de guardia y administración.',
            'telefono_urgencias' => $cfgEmergencia['telefono_urgencias'] ?? '+591 3 300 0000',
            'linea_ambulancia' => $cfgEmergencia['linea_ambulancia'] ?? '160 / 911',
            'ubicacion_urgencias' => $cfgEmergencia['ubicacion_urgencias'] ?? 'Urgencias 24/7 — Planta Baja',
            'pasos' => $pasosPorTipo,
        ];
    }
}
