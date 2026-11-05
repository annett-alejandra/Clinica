<?php

namespace App\Services\Paquetes\P4_GestionCitas;

use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\Paquetes\P4_GestionCitas\HistorialCambioCita;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelarCitaService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Cancelar una cita médica en estado Programada o Confirmada.
     */
    public function cancelar(int $idCita, ?string $motivoCancelacion = null, ?int $idUsuario = null): CitaMedica
    {
        $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($idCita);

        // Regla: Solo citas activas (Programada = 1 o Confirmada = 2) pueden cancelarse
        if (!in_array($cita->id_estado_cita, [1, 2])) {
            throw ValidationException::withMessages([
                'id_estado_cita' => ['Solo se pueden cancelar citas en estado Programada o Confirmada. Estado actual: ' . ($cita->estado?->nombre_estado ?? $cita->id_estado_cita)]
            ]);
        }

        return DB::transaction(function () use ($cita, $motivoCancelacion, $idUsuario) {
            $estadoAnterior = (int) $cita->id_estado_cita;
            $fechaHora = Carbon::parse("{$cita->fecha_cita->format('Y-m-d')} {$cita->hora_cita}");

            // Actualizar la cita a Cancelada (ID = 4)
            $cita->id_estado_cita = 4;
            $cita->motivo_cancelacion = $motivoCancelacion ?: 'Cancelación registrada por administración';
            $cita->save();

            // Registrar en historial_cambios_cita
            HistorialCambioCita::create([
                'id_cita' => $cita->id_cita,
                'id_usuario' => $idUsuario,
                'tipo_cambio' => 'CANCELACION',
                'fecha_hora_anterior' => $fechaHora,
                'fecha_hora_nueva' => null,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 4,
                'observacion' => $cita->motivo_cancelacion,
            ]);

            // Auditoría
            $this->auditService->registrar(
                idUsuario: $idUsuario ?? 1,
                accion: 'CANCELAR_CITA',
                tablaAfectada: 'citas_medicas',
                idRegistroAfectado: $cita->id_cita,
                detalle: [
                    'codigo_unico' => $cita->codigo_unico,
                    'paciente' => $cita->paciente?->nombre_completo,
                    'medico' => $cita->medico?->nombre_completo,
                    'fecha_cita' => $cita->fecha_cita->format('Y-m-d'),
                    'hora_cita' => $cita->hora_cita,
                    'motivo_cancelacion' => $cita->motivo_cancelacion,
                ]
            );

            return $cita->fresh(['paciente', 'medico', 'especialidad', 'estado']);
        });
    }
}
