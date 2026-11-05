<?php

namespace App\Services\Paquetes\P5_ControlAtencion;

use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\Paquetes\P4_GestionCitas\HistorialCambioCita;
use App\Models\User;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CitaAtendidaService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Marcar una cita médica como Atendida por parte del médico responsable.
     * Estado: 2 (Confirmada) o 1 (Programada) -> 3 (Atendida)
     */
    public function registrarAtencion(int $idCita, array $datos, int $idUsuario): CitaMedica
    {
        $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($idCita);
        $usuario = User::with('rol')->findOrFail($idUsuario);

        // Regla: Si el usuario es médico, verificar que la cita corresponda a su ficha médica
        if ($usuario->id_medico && (int) $usuario->id_medico !== (int) $cita->id_medico) {
            throw ValidationException::withMessages([
                'id_medico' => ['No está autorizado para atender citas de otro profesional médico.']
            ]);
        }

        // Regla: Solo citas activas pueden ser atendidas
        if (!in_array((int) $cita->id_estado_cita, [1, 2])) {
            throw ValidationException::withMessages([
                'id_estado_cita' => ['La cita no se encuentra en un estado válido para ser atendida. Estado actual: ' . ($cita->estado?->nombre_estado ?? $cita->id_estado_cita)]
            ]);
        }

        return DB::transaction(function () use ($cita, $datos, $usuario) {
            $estadoAnterior = (int) $cita->id_estado_cita;
            $cita->id_estado_cita = 3; // Atendida
            $cita->observacion_atencion = $datos['observacion_atencion'] ?? 'Consulta médica completada con éxito.';
            $cita->save();

            HistorialCambioCita::create([
                'id_cita' => $cita->id_cita,
                'id_usuario' => $usuario->id_usuario,
                'tipo_cambio' => 'CONSULTA_ATENDIDA',
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 3,
                'observacion' => $cita->observacion_atencion,
            ]);

            $this->auditService->registrar(
                idUsuario: $usuario->id_usuario,
                accion: 'REGISTRAR_CITA_ATENDIDA',
                tablaAfectada: 'citas_medicas',
                idRegistroAfectado: $cita->id_cita,
                detalle: [
                    'codigo_unico' => $cita->codigo_unico,
                    'paciente' => $cita->paciente?->nombre_completo,
                    'medico' => $cita->medico?->nombre_completo,
                    'observacion' => $cita->observacion_atencion,
                ]
            );

            return $cita->fresh(['paciente', 'medico', 'especialidad', 'estado']);
        });
    }
}
