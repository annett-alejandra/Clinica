<?php

namespace App\Http\Controllers\Paquetes\P6_ConsultasHistoriales\CU18_ConsultarMisCitas;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P6_ConsultasHistoriales\MisCitasService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MisCitasController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MisCitasService $misCitasService
    ) {}

    /**
     * Consultar citas del paciente logueado.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $idPaciente = $request->query('id_paciente') ? (int) $request->query('id_paciente') : null;

            $resultado = $this->misCitasService->obtenerMisCitas($idUsuario, $idPaciente);

            return $this->successResponse($resultado, 'Mis citas consultadas exitosamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
