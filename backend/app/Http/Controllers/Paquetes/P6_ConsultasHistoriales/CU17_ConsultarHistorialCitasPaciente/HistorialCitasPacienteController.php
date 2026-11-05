<?php

namespace App\Http\Controllers\Paquetes\P6_ConsultasHistoriales\CU17_ConsultarHistorialCitasPaciente;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P6_ConsultasHistoriales\HistorialCitasPacienteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HistorialCitasPacienteController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected HistorialCitasPacienteService $historialService
    ) {}

    /**
     * Consultar historial de citas por ID o CI.
     */
    public function __invoke(Request $request, string $identificador): JsonResponse
    {
        try {
            $resultado = $this->historialService->obtenerHistorial($identificador);

            return $this->successResponse($resultado, 'Historial clínico de citas obtenido correctamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
