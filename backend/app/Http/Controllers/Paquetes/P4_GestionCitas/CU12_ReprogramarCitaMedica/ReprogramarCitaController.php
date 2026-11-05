<?php

namespace App\Http\Controllers\Paquetes\P4_GestionCitas\CU12_ReprogramarCitaMedica;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P4_GestionCitas\ReprogramarCitaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReprogramarCitaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReprogramarCitaService $reprogramarService
    ) {}

    /**
     * Reprogramar una cita médica.
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'fecha_cita' => 'required|date_format:Y-m-d',
            'hora_cita' => 'required|date_format:H:i',
            'motivo_reprogramacion' => 'nullable|string|max:500',
        ]);

        try {
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $citaActualizada = $this->reprogramarService->reprogramar($id, $validated, $idUsuario);

            return $this->successResponse($citaActualizada, 'Cita médica reprogramada exitosamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
