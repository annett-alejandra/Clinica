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
        $tz = 'America/La_Paz';
        $fechaConsulta = Carbon::parse($fecha, $tz)->startOfDay();
        $hoy = Carbon::now($tz)->startOfDay();

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
            // Buscar horarios del médico en el día de la semana consultado (ordenados cronológicamente)
            $horarios = HorarioAtencion::where('id_medico', $medico->id_medico)
                ->where('dia_semana', $diaSemana)
                ->orderBy('hora_inicio')
                ->get();

            if ($horarios->isEmpty()) {
                continue;
            }

            // Obtener citas ya ocupadas para este médico y fecha (Programadas=1, Confirmadas=2 o Atendidas=3)
            $citasOcupadas = CitaMedica::where('id_medico', $medico->id_medico)
                ->where('fecha_cita', $fechaConsulta->format('Y-m-d'))
                ->whereIn('id_estado_cita', [1, 2, 3])
                ->get(['hora_cita', 'duracion_minutos'])
                ->map(function ($cita) {
                    $inicio = Carbon::parse($cita->hora_cita);
                    $fin = (clone $inicio)->addMinutes($cita->duracion_minutos ?: 30);
                    return ['inicio' => $inicio->format('H:i:s'), 'fin' => $fin->format('H:i:s')];
                })
                ->toArray();

            $bloquesPorHora = [];
            $ahora = Carbon::now('America/La_Paz');

            foreach ($horarios as $horario) {
                $inicioTurno = Carbon::parse($horario->hora_inicio);
                $finTurno = Carbon::parse($horario->hora_fin);
                $duracion = (int) ($horario->duracion_cita_minutos ?: 30);

                $slotActual = clone $inicioTurno;

                while ((clone $slotActual)->addMinutes($duracion)->lte($finTurno)) {
                    $slotInicio = $slotActual->format('H:i:s');
                    $slotFin = (clone $slotActual)->addMinutes($duracion)->format('H:i:s');
                    $horaCorta = substr($slotInicio, 0, 5);

                    // Si la fecha es hoy, detectar turnos que ya pasaron la hora actual
                    $slotEsPasado = false;
                    if ($fechaConsulta->isToday()) {
                        $horaSlotTimestamp = Carbon::parse($fechaConsulta->format('Y-m-d') . ' ' . $slotInicio, 'America/La_Paz');
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

                    $estadoBloque = $ocupado ? 'ocupado' : ($slotEsPasado ? 'pasado' : 'disponible');
                    $nuevoBloque = [
                        'hora_inicio' => $horaCorta,
                        'hora_fin' => substr($slotFin, 0, 5),
                        'duracion_minutos' => $duracion,
                        'estado' => $estadoBloque,
                        'disponible' => $estadoBloque === 'disponible',
                        'ocupado' => $ocupado,
                    ];

                    // Si existen filas solapadas para la misma hora, priorizar estado ocupado
                    if (!isset($bloquesPorHora[$horaCorta]) || $ocupado) {
                        $bloquesPorHora[$horaCorta] = $nuevoBloque;
                    }

                    $slotActual->addMinutes($duracion);
                }
            }

            ksort($bloquesPorHora);
            $todosBloques = array_values($bloquesPorHora);
            $slotsLibres = array_values(array_map(
                fn (array $b) => [
                    'hora_inicio' => $b['hora_inicio'],
                    'hora_fin' => $b['hora_fin'],
                    'duracion_minutos' => $b['duracion_minutos'],
                ],
                array_filter($todosBloques, fn (array $b) => $b['disponible'])
            ));
            $totalOcupados = count(array_filter($todosBloques, fn (array $b) => $b['ocupado']));

            if (!empty($todosBloques)) {
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
                    'total_ocupados' => $totalOcupados,
                    'total_bloques' => count($todosBloques),
                    'cupos' => $slotsLibres,
                    'todos_bloques' => $todosBloques,
                ];
            }
        }

        return $ofertaDisponible;
    }

    /**
     * Sugiere las próximas fechas con turnos libres disponibles para una especialidad y/o médico.
     *
     * @return list<array<string, mixed>>
     */
    public function sugerirProximasFechas(
        int $idEspecialidad,
        ?int $idMedico = null,
        ?string $fechaExcluir = null,
        int $diasVentana = 14,
        int $maxSugerencias = 5
    ): array {
        $tz = 'America/La_Paz';
        $hoy = Carbon::now($tz)->startOfDay();
        $sugerencias = [];

        for ($offset = 0; $offset < $diasVentana; $offset++) {
            $cursor = $hoy->copy()->addDays($offset);
            $fechaIso = $cursor->format('Y-m-d');

            if ($fechaExcluir !== null && $fechaIso === $fechaExcluir) {
                continue;
            }

            try {
                $oferta = $this->consultar($idEspecialidad, $idMedico, $fechaIso);
            } catch (\Throwable) {
                continue;
            }

            $horasLibres = [];
            $medicosConCupos = 0;

            foreach ($oferta as $item) {
                $cupos = $item['cupos'] ?? [];
                if (! empty($cupos)) {
                    $medicosConCupos++;
                    foreach ($cupos as $cupo) {
                        $hora = is_array($cupo) ? ($cupo['hora_inicio'] ?? '') : (string) $cupo;
                        if ($hora !== '') {
                            $horasLibres[] = substr($hora, 0, 5);
                        }
                    }
                }
            }

            if ($horasLibres === []) {
                continue;
            }

            sort($horasLibres);
            $horasUnicas = array_values(array_unique($horasLibres));

            $sugerencias[] = [
                'fecha' => $fechaIso,
                'fecha_legible' => $cursor->format('d/m/Y'),
                'dia_nombre' => match ($cursor->dayOfWeekIso) {
                    1 => 'Lunes',
                    2 => 'Martes',
                    3 => 'Miércoles',
                    4 => 'Jueves',
                    5 => 'Viernes',
                    6 => 'Sábado',
                    default => 'Domingo',
                },
                'es_hoy' => $offset === 0,
                'es_manana' => $offset === 1,
                'total_cupos' => count($horasLibres),
                'primera_hora' => $horasUnicas[0],
                'ultima_hora' => $horasUnicas[count($horasUnicas) - 1],
                'medicos_count' => $medicosConCupos,
            ];

            if (count($sugerencias) >= $maxSugerencias) {
                break;
            }
        }

        return $sugerencias;
    }
}

