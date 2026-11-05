<?php

namespace App\Http\Controllers\Paquetes\P4_GestionCitas\CU13_CancelarCitaMedica;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P4_GestionCitas\CancelarCitaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CancelarCitaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CancelarCitaService $cancelarService
    ) {}

    /**
     * Cancelar una cita médica.
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'motivo_cancelacion' => 'nullable|string|max:500',
        ]);

        try {
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $citaCancelada = $this->cancelarService->cancelar(
                idCita: $id,
                motivoCancelacion: $validated['motivo_cancelacion'] ?? null,
                idUsuario: $idUsuario
            );

            return $this->successResponse($citaCancelada, 'Cita médica cancelada exitosamente y horario liberado.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
