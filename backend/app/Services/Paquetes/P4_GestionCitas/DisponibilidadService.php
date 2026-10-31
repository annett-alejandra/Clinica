<?php

namespace App\Services\Paquetes\P4_GestionCitas;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\HorarioAtencion;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use Carbon\Carbon;
use InvalidArgumentException;

class DisponibilidadService
{
    /**
     * Consulta turnos libres cruzando horarios de atención y citas ya reservadas.
     *
     * @param int $idEspecialidad
     * @param int|null $idMedico
     * @param string $fecha (Y-m-d)
     * @return array
     */
    public function consultar(int $idEspecialidad, ?int $idMedico, string $fecha): array
    {
        $fechaConsulta = Carbon::parse($fecha)->startOfDay();
        $hoy = Carbon::now()->startOfDay();

        if ($fechaConsulta->lt($hoy)) {
            throw new InvalidArgumentException('No se puede consultar disponibilidad médica para fechas anteriores a hoy.');
        }

        // Obtener el día de la semana (1 = Lunes, 7 = Domingo)
        $diaSemana = $fechaConsulta->dayOfWeekIso;

        // Validar especialidad
        $especialidad = Especialidad::findOrFail($idEspecialidad);

        // Médicos habilitados para esta especialidad
        $medicosQuery = Medico::where('estado', true)
            ->whereHas('especialidades', function ($q) use ($idEspecialidad) {
                $q->where('especialidades.id_especialidad', $idEspecialidad);
            });

        if ($idMedico) {
            $medicosQuery->where('id_medico', $idMedico);
        }

        $medicos = $medicosQuery->get();
        $ofertaDisponible = [];

        foreach ($medicos as $medico) {
            // Buscar horarios del médico en el día de la semana consultado
            $horarios = HorarioAtencion::where('id_medico', $medico->id_medico)
                ->where('dia_semana', $diaSemana)
                ->get();

            if ($horarios->isEmpty()) {
                continue;
            }

            // Obtener citas ya ocupadas para este médico y fecha (Programadas=1 o Confirmadas=2)
            $citasOcupadas = CitaMedica::where('id_medico', $medico->id_medico)
                ->where('fecha_cita', $fechaConsulta->format('Y-m-d'))
                ->whereIn('id_estado_cita', [1, 2])
                ->get(['hora_cita', 'duracion_minutos'])
                ->map(function ($cita) {
                    $inicio = Carbon::parse($cita->hora_cita);
                    $fin = (clone $inicio)->addMinutes($cita->duracion_minutos);
                    return ['inicio' => $inicio->format('H:i:s'), 'fin' => $fin->format('H:i:s')];
                })
                ->toArray();

            $slotsLibres = [];
            $ahora = Carbon::now();

            foreach ($horarios as $horario) {
                $inicioTurno = Carbon::parse($horario->hora_inicio);
                $finTurno = Carbon::parse($horario->hora_fin);
                $duracion = $horario->duracion_cita_minutos;

                $slotActual = clone $inicioTurno;

                while ((clone $slotActual)->addMinutes($duracion)->lte($finTurno)) {
                    $slotInicio = $slotActual->format('H:i:s');
                    $slotFin = (clone $slotActual)->addMinutes($duracion)->format('H:i:s');

                    // Si la fecha es hoy, omitir turnos que ya hayan pasado la hora actual
                    $slotEsPasado = false;
                    if ($fechaConsulta->isToday()) {
                        $horaSlotTimestamp = Carbon::parse($fechaConsulta->format('Y-m-d') . ' ' . $slotInicio);
                        if ($horaSlotTimestamp->lte($ahora)) {
                            $slotEsPasado = true;
                        }
                    }

                    // Comprobar si choca con alguna cita ocupada
                    $ocupado = false;
                    foreach ($citasOcupadas as $ocupada) {
                        if ($slotInicio < $ocupada['fin'] && $slotFin > $ocupada['inicio']) {
                            $ocupado = true;
                            break;
                        }
                    }

                    if (!$slotEsPasado && !$ocupado) {
                        $slotsLibres[] = [
                            'hora_inicio' => substr($slotInicio, 0, 5),
                            'hora_fin' => substr($slotFin, 0, 5),
                            'duracion_minutos' => $duracion,
                        ];
                    }

                    $slotActual->addMinutes($duracion);
                }
            }

            if (!empty($slotsLibres)) {
                $ofertaDisponible[] = [
                    'medico' => [
                        'id_medico' => $medico->id_medico,
                        'nombre_completo' => $medico->nombre_completo,
                        'registro_profesional' => $medico->registro_profesional,
                    ],
                    'especialidad' => [
                        'id_especialidad' => $especialidad->id_especialidad,
                        'nombre_especialidad' => $especialidad->nombre_especialidad,
                    ],
                    'fecha' => $fechaConsulta->format('Y-m-d'),
                    'total_cupos' => count($slotsLibres),
                    'cupos' => $slotsLibres,
                ];
            }
        }

        return $ofertaDisponible;
    }
}

