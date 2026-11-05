<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRolClinica
{
    /**
     * Verificar que el usuario tenga uno de los roles permitidos para el caso de uso.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        // En caso de sesión web clásica de demostración
        $rolUsuario = $usuario?->rol?->nombre_rol 
            ?? session('rol_activo') 
            ?? $request->header('X-User-Role') 
            ?? 'Administrador';

        if (!empty($roles) && !in_array($rolUsuario, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado: No posee los permisos requeridos para esta operación.',
                    'rol_actual' => $rolUsuario,
                    'roles_permitidos' => $roles,
                ], 403);
            }

            return redirect()->back()->with('error', "Acceso denegado: Tu rol actual ({$rolUsuario}) no tiene autorización para acceder a este caso de uso.");
        }

        return $next($request);
    }
}

