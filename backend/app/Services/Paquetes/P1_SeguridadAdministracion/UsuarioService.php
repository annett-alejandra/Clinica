<?php

namespace App\Services\Paquetes\P1_SeguridadAdministracion;

use App\Models\Paquetes\P1_SeguridadAdministracion\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UsuarioService
{
    /**
     * Listar todos los usuarios con su rol y entidades asociadas.
     */
    public function listar(): Collection
    {
        return User::query()
            ->with(['rol', 'medico', 'paciente'])
            ->orderBy('id_usuario', 'desc')
            ->get();
    }

    /**
     * Listar catálogo de roles.
     */
    public function listarRoles(): Collection
    {
        return Rol::query()->orderBy('id_rol', 'asc')->get();
    }

    /**
     * Crear un nuevo usuario en el sistema.
     */
    public function crear(array $datos, ?int $operadorId = null): User
    {
        // Validar unicidad
        if (User::where('nombre_usuario', $datos['nombre_usuario'])->exists()) {
            throw new InvalidArgumentException('El nombre de usuario ya está registrado en el sistema.');
        }

        if (User::where('correo', $datos['correo'])->exists()) {
            throw new InvalidArgumentException('El correo electrónico ya está registrado.');
        }

        $usuario = User::create([
            'nombre_usuario' => $datos['nombre_usuario'],
            'correo' => $datos['correo'],
            'password_hash' => Hash::make($datos['password']),
            'estado' => $datos['estado'] ?? true,
            'id_rol' => $datos['id_rol'],
            'id_paciente' => $datos['id_paciente'] ?? null,
            'id_medico' => $datos['id_medico'] ?? null,
        ]);

        AuditService::registrar(
            $operadorId,
            'CREAR_USUARIO',
            'usuarios',
            $usuario->id_usuario,
            ['nombre_usuario' => $usuario->nombre_usuario, 'correo' => $usuario->correo, 'id_rol' => $usuario->id_rol]
        );

        return $usuario->load(['rol', 'medico', 'paciente']);
    }

    /**
     * Actualizar datos de un usuario existente.
     */
    public function actualizar(int $idUsuario, array $datos, ?int $operadorId = null): User
    {
        $usuario = User::findOrFail($idUsuario);

        // Validar unicidad de nombre de usuario si cambia
        if (isset($datos['nombre_usuario']) && $datos['nombre_usuario'] !== $usuario->nombre_usuario) {
            if (User::where('nombre_usuario', $datos['nombre_usuario'])->where('id_usuario', '!=', $idUsuario)->exists()) {
                throw new InvalidArgumentException('El nombre de usuario ya está en uso.');
            }
            $usuario->nombre_usuario = $datos['nombre_usuario'];
        }

        // Validar unicidad de correo si cambia
        if (isset($datos['correo']) && $datos['correo'] !== $usuario->correo) {
            if (User::where('correo', $datos['correo'])->where('id_usuario', '!=', $idUsuario)->exists()) {
                throw new InvalidArgumentException('El correo electrónico ya está registrado.');
            }
            $usuario->correo = $datos['correo'];
        }

        if (isset($datos['id_rol'])) {
            $usuario->id_rol = $datos['id_rol'];
        }

        if (!empty($datos['password'])) {
            $usuario->password_hash = Hash::make($datos['password']);
        }

        if (isset($datos['estado'])) {
            $usuario->estado = (bool) $datos['estado'];
        }

        $usuario->save();

        AuditService::registrar(
            $operadorId,
            'ACTUALIZAR_USUARIO',
            'usuarios',
            $usuario->id_usuario,
            ['id_rol' => $usuario->id_rol, 'estado' => $usuario->estado]
        );

        return $usuario->load(['rol', 'medico', 'paciente']);
    }

    /**
     * Alternar estado activo/inactivo de un usuario.
     */
    public function toggleEstado(int $idUsuario, ?int $operadorId = null): User
    {
        $usuario = User::findOrFail($idUsuario);
        $usuario->estado = !$usuario->estado;
        $usuario->save();

        AuditService::registrar(
            $operadorId,
            $usuario->estado ? 'ACTIVAR_USUARIO' : 'DESACTIVAR_USUARIO',
            'usuarios',
            $usuario->id_usuario,
            ['nuevo_estado' => $usuario->estado]
        );

        return $usuario->load(['rol', 'medico', 'paciente']);
    }
}

