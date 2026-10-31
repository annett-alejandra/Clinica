<?php

namespace App\Services\Paquetes\P4_GestionCitas;

use App\Models\Paquetes\P2_PersonalMedico\HorarioAtencion;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CitaMedicaService
{
    /**
     * Listar citas con filtros opcionales.
     */
    public function listar(?int $idPaciente = null, ?int $idMedico = null, ?string $fecha = null): Collection
    {
        $query = CitaMedica::query()
            ->with(['paciente', 'medico', 'especialidad', 'estadoCita'])
            ->orderBy('fecha_cita', 'desc')
            ->orderBy('hora_cita', 'asc');

        if ($idPaciente) {
            $query->where('id_paciente', $idPaciente);
        }

        if ($idMedico) {
            $query->where('id_medico', $idMedico);
        }

        if ($fecha) {
            $query->where('fecha_cita', $fecha);
        }

        return $query->get()->makeVisible(['motivo', 'observacion_atencion']);
    }

    /**
     * Buscar cita por código único UUID.
     */
    public function buscarPorCodigo(string $codigo): CitaMedica
    {
        return CitaMedica::with(['paciente', 'medico', 'especialidad', 'estadoCita'])
            ->where('codigo_unico', $codigo)
            ->firstOrFail()
            ->makeVisible(['motivo', 'observacion_atencion']);
    }

    /**
     * Programar cita médica verificando no superposición en tiempo real.
     */
    public function programar(array $datos, ?int $operadorId = null): CitaMedica
    {
        $fechaCita = Carbon::parse($datos['fecha_cita'])->format('Y-m-d');
        $horaCita = Carbon::parse($datos['hora_cita'])->format('H:i:s');
        $duracion = (int) ($datos['duracion_minutos'] ?? 30);
        $idMedico = (int) $datos['id_medico'];
        $idPaciente = (int) $datos['id_paciente'];
        $idEspecialidad = (int) $datos['id_especialidad'];

        // Validar que la fecha/hora no sea en el pasado
        $citaDateTime = Carbon::parse("{$fechaCita} {$horaCita}");
        if ($citaDateTime->lt(Carbon::now())) {
            throw new InvalidArgumentException('No es posible programar citas en fechas u horarios que ya han transcurrido.');
        }

        // Validar que el médico atienda en ese día de la semana
        $diaSemana = Carbon::parse($fechaCita)->dayOfWeekIso;
        $tieneHorario = HorarioAtencion::where('id_medico', $idMedico)
            ->where('dia_semana', $diaSemana)
            ->where('hora_inicio', '<=', $horaCita)
            ->where('hora_fin', '>=', Carbon::parse($horaCita)->addMinutes($duracion)->format('H:i:s'))
            ->exists();

        if (!$tieneHorario) {
            throw new InvalidArgumentException('El médico seleccionado no dispone de turno configurado en este día y horario.');
        }

        // Re-comprobar disponibilidad en tiempo real para evitar superposiciones
        $horaFin = Carbon::parse($horaCita)->addMinutes($duracion)->format('H:i:s');

        $superpuesta = CitaMedica::where('id_medico', $idMedico)
            ->where('fecha_cita', $fechaCita)
            ->whereIn('id_estado_cita', [1, 2]) // Programada o Confirmada
            ->where(function ($q) use ($horaCita, $horaFin) {
                $q->where(function ($sub) use ($horaCita, $horaFin) {
                    $sub->where('hora_cita', '<', $horaFin)
                        ->whereRaw("(hora_cita + (duracion_minutos * interval '1 minute')) > ?", [$horaCita]);
                });
            })
            ->exists();

        if ($superpuesta) {
            throw new InvalidArgumentException('El turno seleccionado acaba de ser ocupado. Por favor seleccione otro horario disponible.');
        }

        // Generar UUID único de seguimiento
        $codigoUnico = (string) Str::uuid();

        try {
            return DB::transaction(function () use ($datos, $codigoUnico, $idPaciente, $idMedico, $idEspecialidad, $fechaCita, $horaCita, $duracion, $operadorId) {
                $cita = CitaMedica::create([
                    'codigo_unico' => $codigoUnico,
                    'id_paciente' => $idPaciente,
                    'id_medico' => $idMedico,
                    'id_especialidad' => $idEspecialidad,
                    'id_estado_cita' => 1, // 'Programada' según catálogo oficial
                    'fecha_cita' => $fechaCita,
                    'hora_cita' => $horaCita,
                    'duracion_minutos' => $duracion,
                    'motivo' => $datos['motivo'] ?? null,
                ]);

                AuditService::registrar(
                    $operadorId,
                    'PROGRAMAR_CITA',
                    'citas_medicas',
                    $cita->id_cita,
                    [
                        'codigo_unico' => $codigoUnico,
                        'id_paciente' => $idPaciente,
                        'id_medico' => $idMedico,
                        'fecha_cita' => $fechaCita,
                        'hora_cita' => $horaCita,
                    ]
                );

                return $cita->load(['paciente', 'medico', 'especialidad', 'estadoCita'])->makeVisible(['motivo']);
            });
        } catch (QueryException $e) {
            // Manejar violación del índice de exclusión GiST de PostgreSQL
            if (str_contains($e->getMessage(), 'citas_medicas_no_superpuestas')) {
                throw new InvalidArgumentException('Conflicto de concurrencia: el médico ya tiene una cita reservada en ese lapso de tiempo.');
            }
            throw $e;
        }
    }
}

