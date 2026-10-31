<?php

namespace App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU03_GestionarUsuariosRoles;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P1_SeguridadAdministracion\UsuarioService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class UsuariosController extends Controller
{
    use ApiResponse;

    protected UsuarioService $usuarioService;

    public function __construct(UsuarioService $usuarioService)
    {
        $this->usuarioService = $usuarioService;
    }

    /**
     * Listado de usuarios.
     */
    public function index(): JsonResponse
    {
        $usuarios = $this->usuarioService->listar();
        return $this->success($usuarios, 'Listado de usuarios obtenido correctamente.');
    }

    /**
     * Catálogo de roles para asignación.
     */
    public function roles(): JsonResponse
    {
        $roles = $this->usuarioService->listarRoles();
        return $this->success($roles, 'Catálogo de roles obtenido correctamente.');
    }

    /**
     * Registro de nuevo usuario.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre_usuario' => 'required|string|max:100',
            'correo' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'id_rol' => 'required|integer|exists:roles,id_rol',
            'id_paciente' => 'nullable|integer|exists:pacientes,id_paciente',
            'id_medico' => 'nullable|integer|exists:medicos,id_medico',
            'estado' => 'nullable|boolean',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $usuario = $this->usuarioService->crear($validated, $operadorId);
            return $this->success($usuario, 'Usuario creado con éxito.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al registrar el usuario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualización de usuario.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nombre_usuario' => 'sometimes|string|max:100',
            'correo' => 'sometimes|email|max:255',
            'password' => 'nullable|string|min:6',
            'id_rol' => 'sometimes|integer|exists:roles,id_rol',
            'estado' => 'sometimes|boolean',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $usuario = $this->usuarioService->actualizar($id, $validated, $operadorId);
            return $this->success($usuario, 'Usuario actualizado con éxito.');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al actualizar el usuario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Alternar estado (activar / desactivar).
     */
    public function toggleEstado(Request $request, int $id): JsonResponse
    {
        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $usuario = $this->usuarioService->toggleEstado($id, $operadorId);
            return $this->success($usuario, 'Estado de usuario modificado con éxito.');
        } catch (Throwable $e) {
            return $this->error('Error al modificar estado: ' . $e->getMessage(), 500);
        }
    }
}

