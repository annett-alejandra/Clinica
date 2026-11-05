<?php

namespace App\Http\Controllers\Paquetes\P5_ControlAtencion\CU14_RegistrarConfirmacionAsistenciaInasistencia;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P5_ControlAtencion\ControlAsistenciaService;
use App\Traits\ApiResponse;
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
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $cita = $this->asistenciaService->registrarLlegada($id, $idUsuario);

            return $this->successResponse($cita, 'Llegada física del paciente registrada con éxito en sala de espera.');
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
