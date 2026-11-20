<?php

namespace App\Http\Controllers\Paquetes\P8_AsistenciaInteligente\CU20_AsistenteVirtualIA;

use App\Http\Controllers\Controller;
use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Services\Paquetes\P8_AsistenciaInteligente\ChatbotIAService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ChatbotController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ChatbotIAService $chatbotService
    ) {}

    /**
     * CU20: Vista principal del Asistente Virtual Clínico con IA.
     */
    public function index(Request $request): View
    {
        $estadoMotor = $this->chatbotService->estadoMotor();
        $clinica = config('clinica');
        $esAdmin = false;

        try {
            $esAdmin = (bool) $request->user()?->tieneRol('Administrador');
        } catch (Throwable) {
            $esAdmin = false;
        }

        try {
            $especialidades = Especialidad::where('estado', true)
                ->orderBy('nombre_especialidad')
                ->get(['id_especialidad', 'nombre_especialidad', 'descripcion']);
        } catch (Throwable) {
            $especialidades = collect();
        }

        return view(
            'Paquetes.P8_AsistenciaInteligente.CU20_AsistenteVirtualIA.index',
            compact('estadoMotor', 'especialidades', 'clinica', 'esAdmin')
        );
    }

    /**
     * Devuelve el estado actual del proveedor de IA configurado sin exponer secretos.
     */
    public function estado(): JsonResponse
    {
        try {
            return $this->successResponse(
                $this->chatbotService->estadoMotor(),
                'Estado del asistente virtual obtenido.'
            );
        } catch (Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Recibe una consulta del usuario y devuelve la respuesta generada por IA o fallback local.
     */
    public function mensaje(Request $request): JsonResponse
    {
        try {
            $validado = $request->validate([
                'mensaje' => ['required', 'string', 'min:1', 'max:1000'],
                'historial' => ['nullable', 'array', 'max:12'],
                'historial.*.rol' => ['required_with:historial', 'string', 'in:user,assistant'],
                'historial.*.contenido' => ['required_with:historial', 'string', 'max:2000'],
            ], [
                'mensaje.required' => 'Por favor escribe una consulta para el asistente.',
                'mensaje.string' => 'La consulta debe enviarse como texto válido.',
                'mensaje.min' => 'Por favor escribe una consulta para el asistente.',
                'mensaje.max' => 'La consulta no puede superar los 1000 caracteres.',
                'historial.array' => 'El historial de conversación tiene un formato inválido.',
                'historial.max' => 'El historial excede el máximo de turnos permitidos.',
            ]);

            $texto = trim((string) $validado['mensaje']);
            if ($texto === '') {
                return $this->validationErrorResponse([
                    'mensaje' => ['Por favor escribe una consulta válida para el asistente.'],
                ]);
            }

            $resultado = $this->chatbotService->responder(
                $texto,
                $validado['historial'] ?? [],
                $request->user()
            );

            return $this->successResponse($resultado, 'Consulta procesada correctamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
