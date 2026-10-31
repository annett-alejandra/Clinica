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
            // Filtrar contraseñas y datos sensibles del detalle de auditoría
            if ($detalle !== null) {
                unset($detalle['password'], $detalle['password_hash'], $detalle['token']);
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
}

