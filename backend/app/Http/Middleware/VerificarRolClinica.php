<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRolClinica
{
    /**
     * Verificar que el usuario autenticado tenga uno de los roles permitidos para el caso de uso.
     *
     * - Web: exige sesión iniciada (CU01); sin sesión redirige al login.
     * - API: conserva el comportamiento heredado (cabecera X-User-Role) para clientes de prueba.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $rolUsuario = $usuario?->rol?->nombre_rol;

        if ($rolUsuario === null && $request->is('api/*')) {
            $rolUsuario = $request->header('X-User-Role') ?? 'Administrador';
        }

        if ($rolUsuario === null) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes iniciar sesión para realizar esta operación.',
                ], 401);
            }

            return redirect()->guest(route('login'))
                ->with('error', 'Debes iniciar sesión con tu cuenta oficial para ingresar.');
        }

        // En rutas API exigimos coincidencia estricta con los roles permitidos
        if ($request->is('api/*') && !empty($roles) && !in_array($rolUsuario, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: No posee los permisos requeridos para esta operación.',
                'rol_actual' => $rolUsuario,
                'roles_permitidos' => $roles,
            ], 403);
        }

        // En rutas web, si hay un usuario autenticado con rol restringido y no coincide, avisamos en el portal
        if ($usuario !== null && !empty($roles) && !in_array($rolUsuario, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado: No posee los permisos requeridos para esta operación.',
                    'rol_actual' => $rolUsuario,
                    'roles_permitidos' => $roles,
                ], 403);
            }

            return redirect()->route('portal')->with('error', "Acceso denegado: tu rol ({$rolUsuario}) no tiene autorización para ese módulo.");
        }

        return $next($request);
    }
}
