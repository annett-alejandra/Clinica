<?php

namespace App\Http\Controllers\Paquetes\P5_ControlAtencion\CU14_RegistrarConfirmacionAsistenciaInasistencia;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P5_ControlAtencion\ControlAsistenciaService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ControlAsistenciaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ControlAsistenciaService $asistenciaService
    ) {}

    /**
     * Confirmar la cita (telefónica / presencial previa).
     */
    public function confirmar(Request $request, int $id): JsonResponse
    {
        try {
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $cita = $this->asistenciaService->confirmarCita($id, $idUsuario);

            return $this->successResponse($cita, 'Cita médica confirmada exitosamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Registrar la llegada física a recepción.
     */
    public function registrarLlegada(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'hora_llegada' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            ]);

            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $cita = $this->asistenciaService->registrarLlegada($id, $idUsuario, $validated['hora_llegada'] ?? null);
            $horaStr = Carbon::parse($cita->fecha_hora_llegada)->timezone('America/La_Paz')->format('H:i');

            return $this->successResponse($cita, "Llegada del paciente registrada a las {$horaStr} en sala de espera.");
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Marcar inasistencia.
     */
    public function marcarInasistencia(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        try {
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $cita = $this->asistenciaService->marcarInasistencia($id, $validated['motivo'] ?? null, $idUsuario);

            return $this->successResponse($cita, 'Inasistencia del paciente registrada exitosamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
