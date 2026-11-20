<?php

namespace App\Services\Paquetes\P8_AsistenciaInteligente;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotIAService
{
    private const DIAS_SEMANA = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    /**
     * Devuelve el estado actual del motor de IA sin exponer API keys.
     */
    public function estadoMotor(): array
    {
        $motor = $this->resolverProveedor();

        if ($motor === null) {
            return [
                'configurado' => false,
                'proveedor' => 'asistente_clinico_local',
                'modelo' => 'Base de Conocimiento Clínica',
                'modo' => 'local_fallback',
            ];
        }

        return [
            'configurado' => true,
            'proveedor' => $motor['proveedor'],
            'modelo' => $motor['modelo'],
            'modo' => 'ia_api',
        ];
    }

    /**
     * Procesa una consulta del usuario usando API de IA externa con contexto real
     * de PostgreSQL o fallback clínico local resiliente.
     */
    public function responder(string $mensaje, array $historial = [], ?User $usuario = null): array
    {
        $contexto = $this->construirContextoClinica($usuario);
        $motor = $this->resolverProveedor();
        $fallbackActivado = false;
        $modo = 'local_fallback';
        $proveedorUsado = 'asistente_clinico_local';
        $modeloUsado = 'Base de Conocimiento Clínica';
        $textoRespuesta = null;

        if ($motor !== null) {
            $systemPrompt = $this->construirSystemPrompt($contexto);
            $historialAcotado = array_slice($historial, -8);

            $textoRespuesta = $motor['proveedor'] === 'gemini'
                ? $this->llamarGemini($motor, $systemPrompt, $historialAcotado, $mensaje)
                : $this->llamarOpenAICompatible($motor, $systemPrompt, $historialAcotado, $mensaje);

            if ($textoRespuesta !== null && trim($textoRespuesta) !== '') {
                $modo = 'ia_api';
                $proveedorUsado = $motor['proveedor'];
                $modeloUsado = $motor['modelo'];
            } else {
                $fallbackActivado = true;
            }
        }

        if ($textoRespuesta === null || trim($textoRespuesta) === '') {
            $textoRespuesta = $this->responderConBaseConocimientoLocal($mensaje, $contexto);
        }

        $enlaces = $this->sugerenciasEnlaces($mensaje, $usuario);
        $rolUsuario = $usuario?->nombreRol() ?? 'Visitante';

        // No registrar texto libre clínico del usuario en auditoría; solo metadatos operativos.
        AuditService::registrar(
            $usuario?->id_usuario,
            'CONSULTA_CHATBOT_IA',
            'asistente_ia',
            null,
            [
                'accion_ejecutada' => 'CONSULTA_IA',
                'proveedor' => $proveedorUsado,
                'modelo' => $modeloUsado,
                'modo' => $modo,
                'fallback_activado' => $fallbackActivado,
                'rol' => $rolUsuario,
                'longitud_mensaje' => mb_strlen(trim($mensaje)),
            ]
        );

        return [
            'respuesta' => trim($textoRespuesta),
            'proveedor' => $proveedorUsado,
            'modelo' => $modeloUsado,
            'modo' => $modo,
            'fallback_activado' => $fallbackActivado,
            'enlaces' => $enlaces,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Resuelve el proveedor de IA activo según IA_PROVIDER y las llaves configuradas.
     */
    private function resolverProveedor(): ?array
    {
        $cfg = config('services.ia_chatbot', []);
        $preferido = strtolower((string) ($cfg['provider'] ?? 'auto'));
        $timeout = max(5, (int) ($cfg['timeout'] ?? 20));

        $candidatos = [
            'gemini' => [
                'proveedor' => 'gemini',
                'key' => $cfg['gemini_key'] ?? null,
                'modelo' => $cfg['gemini_model'] ?? 'gemini-2.5-flash',
                'timeout' => $timeout,
            ],
            'groq' => [
                'proveedor' => 'groq',
                'key' => $cfg['groq_key'] ?? null,
                'modelo' => $cfg['groq_model'] ?? 'openai/gpt-oss-120b',
                'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
                'timeout' => $timeout,
            ],
            'openai' => [
                'proveedor' => 'openai',
                'key' => $cfg['openai_key'] ?? null,
                'modelo' => $cfg['openai_model'] ?? 'gpt-4o-mini',
                'endpoint' => 'https://api.openai.com/v1/chat/completions',
                'timeout' => $timeout,
            ],
            'openrouter' => [
                'proveedor' => 'openrouter',
                'key' => $cfg['openrouter_key'] ?? null,
                'modelo' => $cfg['openrouter_model'] ?? 'meta-llama/llama-3.3-70b-instruct:free',
                'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
                'timeout' => $timeout,
            ],
        ];

        if ($preferido !== 'auto' && isset($candidatos[$preferido])) {
            return !empty($candidatos[$preferido]['key']) ? $candidatos[$preferido] : null;
        }

        foreach (['gemini', 'groq', 'openai', 'openrouter'] as $nombre) {
            if (!empty($candidatos[$nombre]['key'])) {
                return $candidatos[$nombre];
            }
        }

        return null;
    }

    /**
     * Construye contexto institucional y datos vivos de PostgreSQL con degradación segura.
     */
    private function construirContextoClinica(?User $usuario): array
    {
        $institucion = [
            'nombre' => config('clinica.nombre', 'Clínica Vida Salud'),
            'ubicacion' => config('clinica.ubicacion', 'Santa Cruz de la Sierra, Bolivia'),
            'telefono' => config('clinica.telefono', '+591 3 300 0000'),
            'urgencias' => config('clinica.urgencias', 'Urgencias 24/7'),
            'areas' => collect(config('clinica.areas', []))
                ->map(fn (array $a) => ($a['title'] ?? '') . ': ' . ($a['description'] ?? ''))
                ->filter()
                ->values()
                ->all(),
        ];

        $especialidades = [];
        $medicos = [];
        $citasPaciente = [];

        try {
            $especialidades = Especialidad::query()
                ->where('estado', true)
                ->orderBy('nombre_especialidad')
                ->get(['id_especialidad', 'nombre_especialidad', 'descripcion'])
                ->map(fn ($e) => [
                    'nombre' => $e->nombre_especialidad,
                    'descripcion' => $e->descripcion,
                ])
                ->all();

            $medicos = Medico::query()
                ->where('estado', true)
                ->with(['especialidades', 'horariosAtencion'])
                ->orderBy('nombre_completo')
                ->get()
                ->map(function ($m) {
                    $horarios = $m->horariosAtencion
                        ->sortBy(['dia_semana', 'hora_inicio'])
                        ->map(function ($h) {
                            $dia = self::DIAS_SEMANA[(int) $h->dia_semana] ?? 'Día ' . $h->dia_semana;
                            return sprintf(
                                '%s %s-%s (citas de %d min)',
                                $dia,
                                substr((string) $h->hora_inicio, 0, 5),
                                substr((string) $h->hora_fin, 0, 5),
                                (int) ($h->duracion_cita_minutos ?: 30)
                            );
                        })
                        ->values()
                        ->all();

                    return [
                        'nombre' => $m->nombre_completo,
                        'registro' => $m->registro_profesional,
                        'especialidades' => $m->especialidades->pluck('nombre_especialidad')->values()->all(),
                        'horarios' => $horarios,
                    ];
                })
                ->all();

            // Solo exponer citas propias vigentes si el usuario autenticado es Paciente.
            if ($usuario && $usuario->tieneRol('Paciente') && $usuario->id_paciente) {
                $citasPaciente = CitaMedica::query()
                    ->with(['medico', 'especialidad', 'estado'])
                    ->where('id_paciente', (int) $usuario->id_paciente)
                    ->whereIn('id_estado_cita', [1, 2])
                    ->orderBy('fecha_cita')
                    ->orderBy('hora_cita')
                    ->limit(5)
                    ->get()
                    ->map(fn ($c) => [
                        'fecha' => $c->fecha_cita ? $c->fecha_cita->format('d/m/Y') : '',
                        'hora' => substr((string) $c->hora_cita, 0, 5),
                        'medico' => $c->medico?->nombre_completo ?? 'Especialista',
                        'especialidad' => $c->especialidad?->nombre_especialidad ?? 'Consulta',
                        'estado' => $c->estado?->nombre_estado ?? 'Programada',
                    ])
                    ->all();
            }
        } catch (Throwable $e) {
            Log::warning('ChatbotIAService: consulta a BD omitida por indisponibilidad: ' . $e->getMessage());
        }

        return [
            'institucion' => $institucion,
            'especialidades' => $especialidades,
            'medicos' => $medicos,
            'citas_paciente' => $citasPaciente,
            'rol_usuario' => $usuario?->nombreRol() ?? 'Visitante',
        ];
    }

    /**
     * Genera el prompt de sistema con reglas estrictas de seguridad clínica y datos reales.
     */
    private function construirSystemPrompt(array $contexto): string
    {
        $inst = $contexto['institucion'];
        $espTexto = empty($contexto['especialidades'])
            ? '- Cardiología, Pediatría, Traumatología, Neurología, Dermatología, Ginecología, Medicina Interna y Oftalmología (catálogo estándar).'
            : collect($contexto['especialidades'])
                ->map(fn ($e) => '- ' . $e['nombre'] . (!empty($e['descripcion']) ? ': ' . $e['descripcion'] : ''))
                ->implode("\n");

        $medTexto = empty($contexto['medicos'])
            ? '- Personal médico disponible en consulta externa de lunes a viernes.'
            : collect($contexto['medicos'])
                ->map(function ($m) {
                    $esps = !empty($m['especialidades']) ? implode(', ', $m['especialidades']) : 'Medicina General';
                    $hrs = !empty($m['horarios']) ? implode('; ', $m['horarios']) : 'Horario habitual Lunes a Viernes 08:00-18:00';
                    return "- {$m['nombre']} ({$esps} | Reg: {$m['registro']}) -> Turnos: {$hrs}";
                })
                ->implode("\n");

        $citasTexto = empty($contexto['citas_paciente'])
            ? 'No aplica o el usuario no tiene citas próximas cargadas.'
            : collect($contexto['citas_paciente'])
                ->map(fn ($c) => "- {$c['fecha']} a las {$c['hora']} con {$c['medico']} ({$c['especialidad']}) [{$c['estado']}]")
                ->implode("\n");

        return <<<PROMPT
Eres el Asistente Virtual oficial de {$inst['nombre']} ({$inst['ubicacion']}, teléfono {$inst['telefono']}, {$inst['urgencias']}).
Tu rol actual ante el usuario es de orientación e información institucional y operativa. Usuario conectado con rol: {$contexto['rol_usuario']}.

REGLAS OBLIGATORIAS:
1. Responde siempre en español claro, cordial, preciso y conciso.
2. Usa EXCLUSIVAMENTE los datos verificados de la clínica listados abajo. Si no conoces un dato (precios específicos, resultados de laboratorio, diagnósticos), indícalo con honestidad y sugiere contactar a recepción al {$inst['telefono']}.
3. NUNCA emitas diagnósticos médicos ni recetes medicamentos. Si el usuario reporta una urgencia médica grave (dolor de pecho intenso, dificultad para respirar, pérdida de conciencia, trauma severo), indícale acudir de inmediato a {$inst['urgencias']} o llamar al {$inst['telefono']}.
4. Explica brevemente cómo realizar trámites en el sistema web cuando pregunten:
   - Consultar disponibilidad: módulo /disponibilidad
   - Agendar cita: módulo /citas (requiere iniciar sesión o registrarse en /registro-paciente)
   - Reprogramar o cancelar cita propia: módulo /mis-citas (para Paciente) o /control-atencion (para Recepcionista)
   - Recuperar contraseña: /recuperar-password

ESPECIALIDADES ACTIVAS EN LA CLÍNICA:
{$espTexto}

MÉDICOS Y HORARIOS DE ATENCIÓN REGISTRADOS:
{$medTexto}

CITAS PRÓXIMAS DEL PACIENTE AUTENTICADO:
{$citasTexto}
PROMPT;
    }

    /**
     * Llama a Google Gemini REST API (generateContent).
     */
    private function llamarGemini(array $motor, string $systemPrompt, array $historial, string $mensaje): ?string
    {
        $contents = [];
        foreach ($historial as $turno) {
            $role = ($turno['rol'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => (string) ($turno['contenido'] ?? '')]],
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $mensaje]],
        ];

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            urlencode($motor['modelo'])
        );

        try {
            $response = Http::timeout($motor['timeout'])
                ->withHeaders([
                    'x-goog-api-key' => $motor['key'],
                ])
                ->acceptJson()
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 700,
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('ChatbotIAService Gemini error HTTP ' . $response->status(), [
                    'body' => substr($response->body(), 0, 300),
                ]);
                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (Throwable $e) {
            Log::warning('ChatbotIAService Gemini excepción: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Llama a proveedores compatibles con el estándar OpenAI (/chat/completions): Groq, OpenAI, OpenRouter.
     */
    private function llamarOpenAICompatible(array $motor, string $systemPrompt, array $historial, string $mensaje): ?string
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($historial as $turno) {
            $messages[] = [
                'role' => ($turno['rol'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => (string) ($turno['contenido'] ?? ''),
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $mensaje];

        try {
            $response = Http::timeout($motor['timeout'])
                ->withToken($motor['key'])
                ->acceptJson()
                ->post($motor['endpoint'], [
                    'model' => $motor['modelo'],
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'max_tokens' => 700,
                ]);

            if (!$response->successful()) {
                Log::warning("ChatbotIAService {$motor['proveedor']} error HTTP " . $response->status(), [
                    'body' => substr($response->body(), 0, 300),
                ]);
                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (Throwable $e) {
            Log::warning("ChatbotIAService {$motor['proveedor']} excepción: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Respuesta inteligente local basada en el catálogo real de PostgreSQL y config('clinica').
     */
    private function responderConBaseConocimientoLocal(string $mensaje, array $contexto): string
    {
        $q = mb_strtolower($mensaje);
        $inst = $contexto['institucion'];

        if (preg_match('/(emergencia|urgencia|dolor.*pecho|infarto|no puedo respirar|sangrado|accidente|desmayo)/u', $q)) {
            return "Si presentas una emergencia médica, acude de inmediato a {$inst['urgencias']} en {$inst['ubicacion']} o comunícate de forma directa al {$inst['telefono']}.\n\nNota: Este asistente no realiza diagnósticos médicos ni reemplaza la valoración presencial de un especialista.";
        }

        if (preg_match('/(receta|medicamento|tomar|pastilla|diagn[oó]stico|qu[eé] enfermedad)/u', $q)) {
            return "Por seguridad del paciente, no emito diagnósticos ni prescribo medicamentos. Te recomiendo agendar una consulta con nuestros especialistas desde la sección de Disponibilidad o Citas para recibir una evaluación médica presencial.";
        }

        if (preg_match('/(mis citas|mi cita|pr[oó]xima|tengo cita)/u', $q)) {
            if (!empty($contexto['citas_paciente'])) {
                $lista = collect($contexto['citas_paciente'])
                    ->map(fn ($c) => "• {$c['fecha']} a las {$c['hora']} — {$c['medico']} ({$c['especialidad']}) [{$c['estado']}]")
                    ->implode("\n");
                return "Estas son tus próximas citas vigentes en {$inst['nombre']}:\n{$lista}\n\nPuedes reprogramarlas o cancelarlas desde el módulo Mis Citas.";
            }
            return "Para ver, reprogramar o cancelar tus citas personales debes iniciar sesión como Paciente e ingresar al módulo Mis Citas (/mis-citas).";
        }

        if (preg_match('/(especialidad|especialidades|[aá]rea|[aá]reas|servicios|atienden)/u', $q)) {
            if (!empty($contexto['especialidades'])) {
                $lista = collect($contexto['especialidades'])
                    ->map(fn ($e) => "• {$e['nombre']}" . (!empty($e['descripcion']) ? ": {$e['descripcion']}" : ''))
                    ->implode("\n");
                return "En {$inst['nombre']} contamos actualmente con estas especialidades médicas activas:\n{$lista}\n\nPuedes consultar turnos libres en tiempo real en /disponibilidad.";
            }
            return "Contamos con especialidades como Cardiología, Pediatría, Traumatología y Ortopedia, Neurología Clínica, Dermatología, Ginecología y Obstetricia, Medicina Interna y Oftalmología.";
        }

        if (preg_match('/(m[eé]dico|doctor|doctora|especialista|horario|turno|atiende|hora|lunes|martes|mi[eé]rcoles|jueves|viernes)/u', $q)) {
            if (!empty($contexto['medicos'])) {
                $coincidencias = collect($contexto['medicos'])->filter(function ($m) use ($q) {
                    $texto = mb_strtolower($m['nombre'] . ' ' . implode(' ', $m['especialidades']));
                    foreach (preg_split('/\s+/u', trim($q)) as $palabra) {
                        if (mb_strlen($palabra) >= 4 && str_contains($texto, $palabra)) {
                            return true;
                        }
                    }
                    return false;
                });

                $seleccion = $coincidencias->isNotEmpty() ? $coincidencias : collect($contexto['medicos'])->take(6);
                $lista = $seleccion->map(function ($m) {
                    $esps = implode(', ', $m['especialidades']);
                    $hrs = !empty($m['horarios']) ? implode(' | ', array_slice($m['horarios'], 0, 2)) : 'Lunes a Viernes 08:00-18:00';
                    return "• {$m['nombre']} ({$esps})\n  Horarios: {$hrs}";
                })->implode("\n");

                return "Personal médico y horarios configurados en {$inst['nombre']}:\n{$lista}\n\nPara ver los bloques horarios exactos libres por fecha, ingresa a Consultar Disponibilidad (/disponibilidad).";
            }
            return "Nuestros especialistas atienden en consulta externa de lunes a viernes (08:00-12:00 y 14:00-18:00) con bloques de 30 minutos. Puedes consultar turnos libres en /disponibilidad.";
        }

        if (preg_match('/(reprogramar|cambiar.*cita|cancelar|anular)/u', $q)) {
            return "Para reprogramar o cancelar una cita vigente:\n• Si eres Paciente: ingresa a Mis Citas (/mis-citas) y utiliza los botones Reprogramar o Cancelar en tu cita programada.\n• Si eres Recepcionista o Administrador: puedes gestionarlo desde Control de Llegadas (/control-atencion) o Agenda Médica (/agenda-medica).";
        }

        if (preg_match('/(agendar|reservar|sacar cita|nueva cita|turno)/u', $q)) {
            return "Para agendar una nueva consulta:\n1. Consulta los horarios libres en Disponibilidad Médica (/disponibilidad) filtrando por especialidad, médico y fecha.\n2. Haz clic en el horario disponible o ve directamente a Programar Cita (/citas) con tu cuenta iniciada.\nSi aún no tienes cuenta, puedes registrarte en /registro-paciente.";
        }

        if (preg_match('/(contrase[nñ]a|clave|recuperar|olvid[eé]|login|iniciar sesi[oó]n)/u', $q)) {
            return "Si olvidaste tu contraseña, ingresa a /recuperar-password, escribe tu correo o usuario registrado, solicita el código de verificación y define una nueva clave (mínimo 8 caracteres con mayúscula, minúscula, número y símbolo).";
        }

        if (preg_match('/(ubicaci[oó]n|d[oó]nde|direcci[oó]n|tel[eé]fono|contacto|llamar)/u', $q)) {
            return "{$inst['nombre']} está ubicada en {$inst['ubicacion']}.\nTeléfono de contacto central: {$inst['telefono']} ({$inst['urgencias']}).";
        }

        return "Hola, soy el Asistente Virtual de {$inst['nombre']} ({$inst['ubicacion']} · {$inst['telefono']}).\n\nPuedo ayudarte con información sobre:\n• Especialidades médicas activas y profesionales\n• Horarios de atención y disponibilidad de turnos\n• Cómo agendar, reprogramar o cancelar una cita\n• Ubicación, contacto y {$inst['urgencias']}\n\n¿Qué consulta deseas realizar?";
    }

    /**
     * Calcula enlaces rápidos pertinentes a la consulta realizada.
     */
    private function sugerenciasEnlaces(string $mensaje, ?User $usuario): array
    {
        $q = mb_strtolower($mensaje);
        $enlaces = [];

        if (preg_match('/(especialidad|m[eé]dico|doctor|horario|disponib|turno|agendar|reservar)/u', $q)) {
            $enlaces[] = ['titulo' => 'Consultar Disponibilidad', 'url' => '/disponibilidad'];
            $enlaces[] = ['titulo' => $usuario ? 'Programar Cita' : 'Iniciar Sesión / Agendar', 'url' => $usuario ? '/citas' : '/login'];
        }

        if (preg_match('/(mis citas|reprogramar|cancelar)/u', $q)) {
            $enlaces[] = ['titulo' => 'Mis Citas Médicas', 'url' => '/mis-citas'];
        }

        if (preg_match('/(contrase[nñ]a|clave|recuperar)/u', $q)) {
            $enlaces[] = ['titulo' => 'Recuperar Contraseña', 'url' => '/recuperar-password'];
        }

        if (preg_match('/(registrar|cuenta|nuevo paciente)/u', $q)) {
            $enlaces[] = ['titulo' => 'Registro de Paciente', 'url' => '/registro-paciente'];
        }

        if ($enlaces === []) {
            $enlaces[] = ['titulo' => 'Ver Disponibilidad', 'url' => '/disponibilidad'];
            $enlaces[] = ['titulo' => $usuario ? 'Agendar Cita' : 'Crear Cuenta Paciente', 'url' => $usuario ? '/citas' : '/registro-paciente'];
        }

        return array_slice($enlaces, 0, 3);
    }
}
