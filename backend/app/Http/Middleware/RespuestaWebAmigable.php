<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los controladores de los casos de uso responden en JSON (los comparte la API).
 * Cuando la petición proviene de un formulario del navegador, este middleware
 * traduce esa respuesta JSON a una redirección con mensaje flash para que la
 * interfaz Blade muestre el resultado sin pantallas de JSON crudo.
 */
class RespuestaWebAmigable
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse || $request->expectsJson() || $request->is('api/*')) {
            return $response;
        }

        $datos = $response->getData(true);
        $mensaje = is_array($datos) ? ($datos['message'] ?? null) : null;

        if ($response->isSuccessful()) {
            return back()->with('status', $mensaje ?: 'Operación realizada correctamente.');
        }

        $redireccion = back()->withInput($request->except(['password', '_token']));
        $errores = is_array($datos) ? ($datos['errors'] ?? null) : null;

        if (is_array($errores) && $errores !== []) {
            return $redireccion->withErrors($errores);
        }

        return $redireccion->with('error', $mensaje ?: 'No se pudo completar la operación.');
    }
}

