<?php

namespace App\Services\Paquetes\P4_GestionCitas;

use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\Paquetes\P4_GestionCitas\HistorialCambioCita;
use App\Models\Paquetes\P2_PersonalMedico\HorarioAtencion;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReprogramarCitaService
{
    public function __construct(
        protected AuditService $auditService,
        protected DisponibilidadService $disponibilidadService
    ) {}

    /**
     * Reprogramar una cita médica a una nueva fecha y hora.
     */
    public function reprogramar(int $idCita, array $datos, ?int $idUsuario = null): CitaMedica
    {
        $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($idCita);

        // Validar que la cita esté en un estado que permita cambios (Programada = 1 o Confirmada = 2)
        if (!in_array($cita->id_estado_cita, [1, 2])) {
            throw ValidationException::withMessages([
                'id_estado_cita' => ['Solo se pueden reprogramar citas en estado Programada o Confirmada. Estado actual: ' . ($cita->estado?->nombre_estado ?? $cita->id_estado_cita)]
            ]);
        }

        $nuevaFecha = Carbon::parse($datos['fecha_cita'])->format('Y-m-d');
        $nuevaHora = Carbon::parse($datos['hora_cita'])->format('H:i:s');
        $motivoReprogramacion = $datos['motivo_reprogramacion'] ?? 'Reprogramación solicitada por el paciente';

        // Validar que la nueva fecha no sea en el pasado
        $nuevoInicio = Carbon::parse("{$nuevaFecha} {$nuevaHora}");
        if ($nuevoInicio->isPast()) {
            throw ValidationException::withMessages([
                'fecha_cita' => ['No se puede reprogramar una cita para una fecha u hora pasada.']
            ]);
        }

        // Validar que no sea la misma fecha y hora
        if ($cita->fecha_cita->format('Y-m-d') === $nuevaFecha && $cita->hora_cita === $nuevaHora) {
            throw ValidationException::withMessages([
                'hora_cita' => ['La nueva fecha y hora deben ser diferentes al horario actual de la cita.']
            ]);
        }

        return DB::transaction(function () use ($cita, $nuevaFecha, $nuevaHora, $nuevoInicio, $motivoReprogramacion, $idUsuario) {
            $fechaHoraAnterior = Carbon::parse("{$cita->fecha_cita->format('Y-m-d')} {$cita->hora_cita}");
            $duracion = (int) $cita->duracion_minutos;

            // Re-verificar disponibilidad en tiempo real para evitar colisiones
            try {
                $oferta = $this->disponibilidadService->consultar(
                    (int) $cita->id_especialidad,
                    (int) $cita->id_medico,
                    $nuevaFecha
                );
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['fecha_cita' => [$e->getMessage()]]);
            }

            $disponibles = collect($oferta)->flatMap(fn (array $item) => $item['cupos'] ?? []);

            $horaNormalizada = Carbon::parse($nuevaHora)->format('H:i');
            $estaDisponible = $disponibles->contains(function ($slot) use ($horaNormalizada) {
                $inicio = is_array($slot) ? ($slot['hora_inicio'] ?? '') : (string) $slot;
                return substr($inicio, 0, 5) === $horaNormalizada;
            });

            if (!$estaDisponible) {
                throw ValidationException::withMessages([
                    'hora_cita' => ['El turno seleccionado ya no se encuentra disponible. Por favor elija otro turno.']
                ]);
            }

            $estadoAnteriorId = (int) $cita->id_estado_cita;
            $estadoAnteriorNombre = $cita->estado?->nombre_estado ?? ($estadoAnteriorId === 2 ? 'Confirmada' : 'Programada');

            // Actualizar la cita
            $cita->fecha_cita = $nuevaFecha;
            $cita->hora_cita = $nuevaHora;
            $cita->id_estado_cita = 1; // Vuelve a Programada si estaba confirmada
            $cita->save();

            // Registrar en historial_cambios_cita
            HistorialCambioCita::create([
                'id_cita' => $cita->id_cita,
                'id_usuario' => $idUsuario,
                'tipo_cambio' => 'REPROGRAMACION',
                'fecha_hora_anterior' => $fechaHoraAnterior,
                'fecha_hora_nueva' => $nuevoInicio,
                'estado_anterior' => $estadoAnteriorId,
                'estado_nuevo' => 1,
                'observacion' => $motivoReprogramacion,
            ]);

            // Registrar en bitácora general de auditoría
            AuditService::registrarCita(
                cita: $cita,
                operadorId: $idUsuario ?? auth()->id(),
                accionBd: 'REPROGRAMAR_CITA',
                accionEjecutada: 'REPROGRAMACION',
                estadoAnterior: $estadoAnteriorNombre,
                estadoNuevo: 'Programada',
                motivoJustificacion: $motivoReprogramacion,
                extra: [
                    'horario_anterior' => $fechaHoraAnterior->format('Y-m-d H:i'),
                    'horario_nuevo' => $nuevoInicio->format('Y-m-d H:i'),
                ]
            );

            return $cita->fresh(['paciente', 'medico', 'especialidad', 'estado']);
        });
    }
}
