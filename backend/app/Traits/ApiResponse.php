<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public function success(mixed $data = null, string $message = 'Operación exitosa', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function error(string $message = 'Error en la operación', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /** Alias usado por los controladores de los paquetes 3, 4, 5 y 7. */
    public function successResponse(mixed $data = null, string $message = 'Operación exitosa', int $statusCode = 200): JsonResponse
    {
        return $this->success($data, $message, $statusCode);
    }

    /** Errores de validación de reglas de negocio (422) con el detalle por campo. */
    public function validationErrorResponse(array $errors, string $message = 'Los datos enviados no son válidos.'): JsonResponse
    {
        $primero = collect($errors)->flatten()->first();

        return $this->error($primero ?: $message, 422, $errors);
    }

    /** Fallos inesperados: el detalle técnico solo se expone en modo depuración. */
    public function serverErrorResponse(string $detalle = '', string $message = 'Ocurrió un error interno al procesar la solicitud.'): JsonResponse
    {
        report(new \RuntimeException($detalle ?: $message));

        return $this->error(config('app.debug') && $detalle !== '' ? $detalle : $message, 500);
    }
}
