<?php

namespace App\Services\Paquetes\P1_SeguridadAdministracion;

use App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditService
{
    /**
     * Registra un evento en la tabla de auditoría inmutable.
     *
     * @param int|null $idUsuario
     * @param string $accion
     * @param string $tablaAfectada
     * @param int|null $idRegistroAfectado
     * @param array|null $detalle
     * @return Auditoria|null
     */
    public static function registrar(
        ?int $idUsuario,
        string $accion,
        string $tablaAfectada,
        ?int $idRegistroAfectado = null,
        ?array $detalle = null
    ): ?Auditoria {
        try {
            // Filtrar contraseñas y datos sensibles del detalle de auditoría y enriquecer con IP/dispositivo
            $detalle = $detalle ?? [];
            unset($detalle['password'], $detalle['password_hash'], $detalle['token']);
            if (app()->bound('request')) {
                $req = request();
                if (!isset($detalle['ip'])) {
                    $detalle['ip'] = $req?->ip();
                }
                if (!isset($detalle['user_agent']) && $req?->userAgent()) {
                    $detalle['user_agent'] = substr((string) $req->userAgent(), 0, 180);
                }
            }

            return Auditoria::create([
                'id_usuario' => $idUsuario,
                'accion' => strtoupper(substr($accion, 0, 100)),
                'tabla_afectada' => strtolower(substr($tablaAfectada, 0, 100)),
                'id_registro_afectado' => $idRegistroAfectado,
                'detalle' => $detalle,
            ]);
        } catch (Throwable $e) {
            Log::error('Fallo al registrar auditoría: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Registra un evento clínico trazable sobre citas médicas con transición de estados,
     * datos completos del paciente (ID y CI), médico, especialidad, horario y justificación.
     */
    public static function registrarCita(
        \App\Models\Paquetes\P4_GestionCitas\CitaMedica $cita,
        ?int $operadorId,
        string $accionBd,
        string $accionEjecutada,
        ?string $estadoAnterior,
        string $estadoNuevo,
        ?string $motivoJustificacion = null,
        array $extra = []
    ): ?Auditoria {
        $cita->loadMissing(['paciente', 'medico', 'especialidad']);

        $payload = array_merge([
            'accion_ejecutada' => $accionEjecutada,
            'codigo_unico' => $cita->codigo_unico,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'motivo_justificacion' => $motivoJustificacion,
            'paciente' => [
                'id_paciente' => $cita->id_paciente,
                'ci' => $cita->paciente?->ci,
                'nombre_completo' => $cita->paciente?->nombre_completo,
            ],
            'medico' => [
                'id_medico' => $cita->id_medico,
                'nombre_completo' => $cita->medico?->nombre_completo,
            ],
            'especialidad' => [
                'id_especialidad' => $cita->id_especialidad,
                'nombre_especialidad' => $cita->especialidad?->nombre_especialidad,
            ],
            'fecha_cita' => $cita->fecha_cita instanceof \Carbon\CarbonInterface
                ? $cita->fecha_cita->format('Y-m-d')
                : (string) $cita->fecha_cita,
            'hora_cita' => substr((string) $cita->hora_cita, 0, 5),
            'ip' => app()->bound('request') ? request()?->ip() : 'CLI',
        ], $extra);

        return self::registrar(
            $operadorId,
            $accionBd,
            'citas_medicas',
            $cita->id_cita,
            $payload
        );
    }
}

