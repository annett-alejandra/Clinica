<?php

namespace App\Http\Controllers\Paquetes\P5_ControlAtencion\CU15_RegistrarCitaAtendida;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P5_ControlAtencion\CitaAtendidaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CitaAtendidaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CitaAtendidaService $atendidaService
    ) {}

    /**
     * Registrar cita como atendida.
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'observacion_atencion' => 'nullable|string|max:2000',
        ]);

        try {
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $citaAtendida = $this->atendidaService->registrarAtencion($id, $validated, $idUsuario);

            return $this->successResponse($citaAtendida, 'Consulta médica registrada como atendida exitosamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
