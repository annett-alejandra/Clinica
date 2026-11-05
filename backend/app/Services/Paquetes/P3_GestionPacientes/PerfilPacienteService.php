<?php

namespace App\Services\Paquetes\P3_GestionPacientes;

use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\User;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerfilPacienteService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Obtener el perfil del paciente vinculado al usuario autenticado o por id_paciente.
     */
    public function obtenerPerfil(int $idUsuario, ?int $idPaciente = null): array
    {
        $usuario = User::with(['rol', 'paciente'])->findOrFail($idUsuario);

        $paciente = null;
        if ($usuario->id_paciente) {
            $paciente = Paciente::find($usuario->id_paciente);
        } elseif ($idPaciente) {
            $paciente = Paciente::find($idPaciente);
        }

        if (!$paciente) {
            throw ValidationException::withMessages([
                'paciente' => ['No se encontró una ficha de paciente asociada a esta cuenta.']
            ]);
        }

        return [
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_usuario' => $usuario->nombre_usuario,
                'correo' => $usuario->correo,
                'rol' => $usuario->rol?->nombre_rol,
            ],
            'paciente' => $paciente
        ];
    }

    /**
     * Actualizar los datos de contacto y emergencia del paciente.
     */
    public function actualizarPerfil(int $idUsuario, array $datos): Paciente
    {
        $usuario = User::findOrFail($idUsuario);
        $paciente = null;

        if ($usuario->id_paciente) {
            $paciente = Paciente::findOrFail($usuario->id_paciente);
        } elseif (!empty($datos['id_paciente'])) {
            $paciente = Paciente::findOrFail($datos['id_paciente']);
        } else {
            throw ValidationException::withMessages([
                'paciente' => ['No se especificó la ficha del paciente a actualizar.']
            ]);
        }

        return DB::transaction(function () use ($usuario, $paciente, $datos) {
            $pacienteAnterior = $paciente->replicate();

            $paciente->fill([
                'telefono' => $datos['telefono'] ?? $paciente->telefono,
                'correo' => $datos['correo'] ?? $paciente->correo,
                'direccion_domicilio' => $datos['direccion_domicilio'] ?? $paciente->direccion_domicilio,
                'contacto_emergencia' => $datos['contacto_emergencia'] ?? $paciente->contacto_emergencia,
                'telefono_emergencia' => $datos['telefono_emergencia'] ?? $paciente->telefono_emergencia,
            ]);

            $paciente->save();

            // Si el correo cambió y el usuario tiene el mismo correo, sincronizar usuario
            if (!empty($datos['correo']) && $usuario->id_paciente === $paciente->id_paciente) {
                $usuario->correo = $datos['correo'];
                $usuario->save();
            }

            $this->auditService->registrar(
                idUsuario: $usuario->id_usuario,
                accion: 'ACTUALIZAR_PERFIL_PACIENTE',
                tablaAfectada: 'pacientes',
                idRegistroAfectado: $paciente->id_paciente,
                detalle: [
                    'ci' => $paciente->ci,
                    'cambios' => $paciente->getChanges(),
                ]
            );

            return $paciente;
        });
    }
}
