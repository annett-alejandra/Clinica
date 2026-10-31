<?php

namespace App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU01_IniciarSesion;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * CU01: Inicio de sesión con correo o nombre de usuario.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identificador' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $resultado = $this->authService->login($validated['identificador'], $validated['password']);
            return $this->success($resultado, 'Sesión iniciada con éxito.');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (Throwable $e) {
            return $this->error('Error interno al procesar el inicio de sesión: ' . $e->getMessage(), 500);
        }
    }

    /**
     * CU02: Cierre de sesión seguro y bitácora.
     */
    public function logout(Request $request): JsonResponse
    {
        $idUsuario = $request->input('id_usuario') ?? auth()->id();
        $this->authService->logout($idUsuario);

        return $this->success(null, 'Sesión cerrada correctamente.');
    }
}

