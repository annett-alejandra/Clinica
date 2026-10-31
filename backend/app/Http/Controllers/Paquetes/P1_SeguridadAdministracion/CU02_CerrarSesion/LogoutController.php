<?php

namespace App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU02_CerrarSesion;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * CU02: Cierre de sesión y registro en bitácora.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $idUsuario = $request->input('id_usuario') ?? auth()->id();
        $this->authService->logout($idUsuario);

        return $this->success(null, 'Sesión finalizada e invalidada correctamente.');
    }
}

