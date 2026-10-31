<?php

namespace App\Services\Paquetes\P1_SeguridadAdministracion;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AuthService
{
    /**
     * Autentica a un usuario y genera un token o sesión.
     *
     * @param string $identificador Correo o nombre de usuario
     * @param string $password
     * @return array
     * @throws InvalidArgumentException
     */
    public function login(string $identificador, string $password): array
    {
        $usuario = User::query()
            ->with(['rol', 'medico', 'paciente'])
            ->where('correo', $identificador)
            ->orWhere('nombre_usuario', $identificador)
            ->first();

        if (!$usuario) {
            throw new InvalidArgumentException('Las credenciales proporcionadas no son válidas.');
        }

        if (!$usuario->estado) {
            throw new InvalidArgumentException('La cuenta de usuario se encuentra inactiva o suspendida.');
        }

        if (!Hash::check($password, $usuario->password_hash)) {
            AuditService::registrar(
                $usuario->id_usuario,
                'INTENTO_LOGIN_FALLIDO',
                'usuarios',
                $usuario->id_usuario,
                ['ip' => request()->ip(), 'identificador' => $identificador]
            );
            throw new InvalidArgumentException('Las credenciales proporcionadas no son válidas.');
        }

        // Generar un token seguro para consumo API
        $token = hash('sha256', Str::random(40) . microtime(true));

        AuditService::registrar(
            $usuario->id_usuario,
            'LOGIN',
            'usuarios',
            $usuario->id_usuario,
            ['ip' => request()->ip(), 'rol' => $usuario->rol?->nombre_rol]
        );

        return [
            'token' => $token,
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_usuario' => $usuario->nombre_usuario,
                'correo' => $usuario->correo,
                'rol' => $usuario->rol?->nombre_rol,
                'id_rol' => $usuario->id_rol,
                'id_medico' => $usuario->id_medico,
                'id_paciente' => $usuario->id_paciente,
                'perfil' => $usuario->medico?->nombre_completo ?? $usuario->paciente?->nombre_completo ?? $usuario->nombre_usuario,
            ],
        ];
    }

    /**
     * Cierra la sesión activa y registra el evento de salida.
     *
     * @param int|null $idUsuario
     * @return void
     */
    public function logout(?int $idUsuario): void
    {
        if ($idUsuario) {
            AuditService::registrar(
                $idUsuario,
                'LOGOUT',
                'usuarios',
                $idUsuario,
                ['ip' => request()->ip()]
            );
        }
    }
}

