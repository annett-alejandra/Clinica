<?php

namespace App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU02_CerrarSesion;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CU02 (versión web): cierra la sesión del navegador, invalida la sesión
 * y registra el evento de salida en la bitácora.
 */
class LogoutWebController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $this->authService->logout(Auth::id());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio')->with('status', 'Sesión cerrada correctamente.');
    }
}

