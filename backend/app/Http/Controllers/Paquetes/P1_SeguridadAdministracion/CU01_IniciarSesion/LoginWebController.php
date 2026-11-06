<?php

namespace App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU01_IniciarSesion;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * CU01 (versión web): inicia la sesión del navegador reutilizando AuthService
 * para validar credenciales y registrar la bitácora de accesos.
 */
class LoginWebController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'credencial' => 'required|string|max:150',
            'password' => 'required|string|max:255',
        ]);

        try {
            $resultado = $this->authService->login($validated['credencial'], $validated['password']);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput($request->only('credencial'))
                ->with('error', $e->getMessage());
        }

        Auth::loginUsingId($resultado['usuario']['id_usuario']);
        $request->session()->regenerate();

        return redirect()->intended(route('portal'))
            ->with('status', 'Bienvenido(a), ' . $resultado['usuario']['perfil'] . '.');
    }
}

