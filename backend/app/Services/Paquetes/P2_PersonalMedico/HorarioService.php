<?php

namespace App\Services\Paquetes\P2_PersonalMedico;

use App\Models\Paquetes\P2_PersonalMedico\HorarioAtencion;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class HorarioService
{
    /**
     * Listar horarios de un médico o de toda la clínica.
     */
    public function listar(?int $idMedico = null): Collection
    {
        $query = HorarioAtencion::query()->with('medico')->orderBy('dia_semana')->orderBy('hora_inicio');
        if ($idMedico) {
            $query->where('id_medico', $idMedico);
        }
        return $query->get();
    }

    /**
     * Registrar un turno de horario verificando no superposición.
     */
    public function crear(array $datos, ?int $operadorId = null): HorarioAtencion
    {
        $medico = Medico::findOrFail($datos['id_medico']);

        $horaInicio = Carbon::parse($datos['hora_inicio'])->format('H:i:s');
        $horaFin = Carbon::parse($datos['hora_fin'])->format('H:i:s');
        $duracion = (int) $datos['duracion_cita_minutos'];
        $diaSemana = (int) $datos['dia_semana'];

        if ($diaSemana < 1 || $diaSemana > 7) {
            throw new InvalidArgumentException('El día de la semana debe estar entre 1 (Lunes) y 7 (Domingo).');
        }

        if ($horaFin <= $horaInicio) {
            throw new InvalidArgumentException('La hora de fin debe ser posterior a la hora de inicio.');
        }

        $minutosIntervalo = Carbon::parse($horaInicio)->diffInMinutes(Carbon::parse($horaFin));
        if ($duracion <= 0 || $duracion > $minutosIntervalo) {
            throw new InvalidArgumentException("La duración de la cita ({$duracion} min) excede o es incompatible con el intervalo configurado ({$minutosIntervalo} min).");
        }

        // Prevenir superposición de horarios para el mismo médico y día
        $superpuesto = HorarioAtencion::where('id_medico', $medico->id_medico)
            ->where('dia_semana', $diaSemana)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where(function ($sub) use ($horaInicio, $horaFin) {
                    $sub->where('hora_inicio', '<', $horaFin)
                        ->where('hora_fin', '>', $horaInicio);
                });
            })
            ->exists();

        if ($superpuesto) {
            throw new InvalidArgumentException("El médico {$medico->nombre_completo} ya tiene un horario asignado que se superpone en este día.");
        }

        $horario = HorarioAtencion::create([
            'id_medico' => $medico->id_medico,
            'dia_semana' => $diaSemana,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'duracion_cita_minutos' => $duracion,
        ]);

        AuditService::registrar(
            $operadorId ?? auth()->id(),
            'CREAR_HORARIO_ATENCION',
            'horarios_atencion',
            $horario->id_horario,
            [
                'accion_ejecutada' => 'CREACION',
                'estado_anterior' => null,
                'estado_nuevo' => 'Activo',
                'medico' => [
                    'id_medico' => $medico->id_medico,
                    'nombre_completo' => $medico->nombre_completo,
                ],
                'dia_semana' => $diaSemana,
                'hora_inicio' => substr($horaInicio, 0, 5),
                'hora_fin' => substr($horaFin, 0, 5),
                'duracion_cita_minutos' => $duracion,
                'motivo_justificacion' => 'Configuración de turno semanal de atención médica',
            ]
        );

        return $horario->load('medico');
    }

    /**
     * Eliminar un horario de atención.
     */
    public function eliminar(int $idHorario, ?int $operadorId = null): void
    {
        $horario = HorarioAtencion::with('medico')->findOrFail($idHorario);
        $horarioData = [
            'accion_ejecutada' => 'ELIMINACION',
            'estado_anterior' => 'Activo',
            'estado_nuevo' => 'Eliminado',
            'medico' => [
                'id_medico' => $horario->id_medico,
                'nombre_completo' => $horario->medico?->nombre_completo,
            ],
            'dia_semana' => $horario->dia_semana,
            'hora_inicio' => substr((string) $horario->hora_inicio, 0, 5),
            'hora_fin' => substr((string) $horario->hora_fin, 0, 5),
            'motivo_justificacion' => 'Eliminación de turno de atención médica',
        ];
        $horario->delete();

        AuditService::registrar(
            $operadorId ?? auth()->id(),
            'ELIMINAR_HORARIO_ATENCION',
            'horarios_atencion',
            $idHorario,
            $horarioData
        );
    }
}

