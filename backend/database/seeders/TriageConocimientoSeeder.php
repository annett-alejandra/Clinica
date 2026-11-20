<?php

namespace Database\Seeders;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\Paquetes\P4_GestionCitas\ConsultaTriage;
use App\Models\User;
use App\Services\Triage\KnowledgeBaseSyncService;
use App\Services\Triage\VectorMathService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TriageConocimientoSeeder extends Seeder
{
    public function run(KnowledgeBaseSyncService $knowledgeBase, VectorMathService $vectorMath): void
    {
        $resultado = $knowledgeBase->sincronizarPerfiles(false);
        $consultasCargadas = $this->cargarHistorialYAlertasDemo($vectorMath);

        if ($this->command) {
            $this->command->info(sprintf(
                'Base de conocimientos clínica vectorizada: %d perfiles sincronizados (proveedor: %s). Evaluaciones triage demo agregadas: %d.',
                $resultado['sincronizados'],
                $resultado['proveedor'],
                $consultasCargadas
            ));
        }
    }

    /**
     * Pobla de forma idempotente evaluaciones históricas y alertas rojas realistas
     * para demostración del Panel de Monitoreo y Triage Inteligente.
     */
    private function cargarHistorialYAlertasDemo(VectorMathService $vectorMath): int
    {
        if (! Schema::hasTable('consultas_triage')) {
            return 0;
        }

        $pacientes = Paciente::where('estado', true)->orderBy('id_paciente')->get()->values();
        $medicos = Medico::where('estado', true)->with('especialidades')->orderBy('id_medico')->get()->values();
        $especialidades = Especialidad::where('estado', true)->get();
        $adminId = User::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'Administrador'))->value('id_usuario')
            ?? User::value('id_usuario');

        if ($pacientes->isEmpty() || $medicos->isEmpty() || $especialidades->isEmpty()) {
            return 0;
        }

        $buscarEsp = function (string $needle) use ($especialidades) {
            return $especialidades->first(
                fn ($e) => str_contains(mb_strtolower($e->nombre_especialidad), mb_strtolower($needle))
            ) ?? $especialidades->first();
        };

        $buscarMedParaEsp = function (int $idEsp) use ($medicos) {
            return $medicos->first(
                fn ($m) => $m->especialidades->contains('id_especialidad', $idEsp)
            ) ?? $medicos->first();
        };

        $casos = [
            // 2 Alertas Rojas activas pendientes (para visibilidad inmediata en el monitor)
            [
                'paciente_idx' => 0,
                'esp' => 'Cardiología',
                'sintomas' => 'Siento un dolor punzante en el pecho al respirar hondo, opresión torácica y mareos leves desde hace 2 horas',
                'urgencia' => 'EMERGENCIA',
                'es_emergencia' => true,
                'palabras' => ['dolor punzante en el pecho', 'dolor en el pecho al respirar', 'pecho + mareos'],
                'afinidad' => 96.4,
                'explicacion' => 'ATENCIÓN PRIORITARIA: Se detectaron signos de alarma cardiopulmonar que requieren evaluación inmediata en Urgencias 24/7. Para seguimiento especializado se sugiere Cardiología (96.4% de afinidad).',
                'atendida' => false,
                'minutos_atras' => 14,
            ],
            [
                'paciente_idx' => 1,
                'esp' => 'Neurología',
                'sintomas' => 'Mi familiar presenta dificultad para hablar repentina, cara torcida y adormecimiento súbito en el brazo izquierdo',
                'urgencia' => 'EMERGENCIA',
                'es_emergencia' => true,
                'palabras' => ['dificultad para hablar', 'cara torcida', 'adormecimiento + hablar'],
                'afinidad' => 95.2,
                'explicacion' => 'ATENCIÓN PRIORITARIA: Sospecha de déficit neurológico agudo (ventana de atención inmediata). Se activó protocolo neurológico de emergencia y se sugiere Neurología Clínica (95.2% de afinidad).',
                'atendida' => false,
                'minutos_atras' => 38,
            ],
            // Evaluaciones previas (incluye alertas rojas ya atendidas y casos prioritarios/normales)
            [
                'paciente_idx' => 2,
                'esp' => 'Pediatría',
                'sintomas' => 'Mi hijo de 4 años tiene fiebre alta desde anoche, tos con flema y dolor de oído',
                'urgencia' => 'PRIORITARIO',
                'es_emergencia' => false,
                'palabras' => ['fiebre alta'],
                'afinidad' => 93.8,
                'explicacion' => 'Recomendamos Pediatría (93.8% de afinidad) por coincidencia directa en fiebre infantil, tos infantil y dolor de oído infantil.',
                'atendida' => true,
                'minutos_atras' => 310,
            ],
            [
                'paciente_idx' => 3,
                'esp' => 'Traumatología',
                'sintomas' => 'Me caí jugando fútbol y tengo dolor intenso e inflamación severa en la rodilla al apoyar el pie',
                'urgencia' => 'PRIORITARIO',
                'es_emergencia' => false,
                'palabras' => ['dolor intenso', 'inflamación severa'],
                'afinidad' => 94.5,
                'explicacion' => 'Recomendamos Traumatología y Ortopedia (94.5% de afinidad) por su estrecha relación clínica con dolor de rodilla, lesión de menisco y traumatismo articular.',
                'atendida' => true,
                'minutos_atras' => 1420,
            ],
            [
                'paciente_idx' => 4,
                'esp' => 'Cardiología',
                'sintomas' => 'Palpitaciones frecuentes, falta de aire súbita al subir gradas y presión arterial elevada',
                'urgencia' => 'EMERGENCIA',
                'es_emergencia' => true,
                'palabras' => ['falta de aire subita', 'palpitaciones frecuentes'],
                'afinidad' => 95.7,
                'explicacion' => 'ATENCIÓN PRIORITARIA: Se detectó falta de aire súbita asociada a palpitaciones. Alerta atendida oportunamente por el equipo de guardia.',
                'atendida' => true,
                'minutos_atras' => 2890,
            ],
            [
                'paciente_idx' => 1,
                'esp' => 'Dermatología',
                'sintomas' => 'Me salieron ronchas rojas y sarpullido con mucha picazón en los brazos después de cambiar de crema',
                'urgencia' => 'NORMAL',
                'es_emergencia' => false,
                'palabras' => [],
                'afinidad' => 91.2,
                'explicacion' => 'Recomendamos Dermatología (91.2% de afinidad) por coincidencia clínica directa con sarpullido, ronchas y alergias cutáneas.',
                'atendida' => true,
                'minutos_atras' => 4320,
            ],
            [
                'paciente_idx' => 0,
                'esp' => 'Neurología',
                'sintomas' => 'Dolor de cabeza pulsátil tipo migraña con sensibilidad a la luz y hormigueo en la mano desde hace 2 días',
                'urgencia' => 'NORMAL',
                'es_emergencia' => false,
                'palabras' => [],
                'afinidad' => 92.6,
                'explicacion' => 'Recomendamos Neurología Clínica (92.6% de afinidad) por su relación directa con migraña, cefalea y parestesias.',
                'atendida' => true,
                'minutos_atras' => 5760,
            ],
            [
                'paciente_idx' => 3,
                'esp' => 'Cardiología',
                'sintomas' => 'Presentó desmayo repentino luego de dolor opresivo en el pecho y sudoración fría',
                'urgencia' => 'EMERGENCIA',
                'es_emergencia' => true,
                'palabras' => ['desmayo repentino', 'dolor opresivo en el pecho'],
                'afinidad' => 97.1,
                'explicacion' => 'ATENCIÓN PRIORITARIA: Síncope y dolor opresivo en el pecho. Paciente estabilizado en sala de shock tras despacho de Alerta Roja.',
                'atendida' => true,
                'minutos_atras' => 7200,
            ],
        ];

        $insertados = 0;

        foreach ($casos as $caso) {
            if (ConsultaTriage::where('sintomas_texto', $caso['sintomas'])->exists()) {
                continue;
            }

            $paciente = $pacientes[$caso['paciente_idx'] % $pacientes->count()];
            $esp = $buscarEsp($caso['esp']);
            $med = $buscarMedParaEsp((int) $esp->id_especialidad);
            $fechaRegistro = now()->subMinutes($caso['minutos_atras']);

            $registro = ConsultaTriage::create([
                'id_usuario' => $adminId,
                'id_paciente' => $paciente->id_paciente,
                'sintomas_texto' => $caso['sintomas'],
                'vector_consulta' => $vectorMath->construirVectorLocal($caso['sintomas']),
                'proveedor_ia' => 'groq',
                'nivel_urgencia' => $caso['urgencia'],
                'es_emergencia' => $caso['es_emergencia'],
                'palabras_emergencia_detectadas' => $caso['palabras'],
                'id_especialidad_recomendada' => $esp->id_especialidad,
                'id_medico_recomendado' => $med->id_medico,
                'puntaje_afinidad' => $caso['afinidad'],
                'explicacion_clinica' => $caso['explicacion'],
                'recomendaciones_json' => [[
                    'medico' => [
                        'id_medico' => $med->id_medico,
                        'nombre_completo' => $med->nombre_completo,
                    ],
                    'especialidad' => [
                        'id_especialidad' => $esp->id_especialidad,
                        'nombre_especialidad' => $esp->nombre_especialidad,
                    ],
                    'porcentaje_afinidad' => $caso['afinidad'],
                ]],
                'alerta_atendida' => $caso['atendida'],
                'id_usuario_atendio' => ($caso['es_emergencia'] && $caso['atendida']) ? $adminId : null,
                'fecha_atencion_alerta' => ($caso['es_emergencia'] && $caso['atendida'])
                    ? $fechaRegistro->copy()->addMinutes(6)
                    : null,
            ]);

            DB::table('consultas_triage')
                ->where('id_triage', $registro->id_triage)
                ->update(['fecha_registro' => $fechaRegistro]);

            $insertados++;
        }

        return $insertados;
    }
}
