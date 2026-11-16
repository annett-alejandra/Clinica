<?php

namespace App\Http\Controllers\Paquetes\P4_GestionCitas\CU12_ReprogramarCitaMedica;

use App\Http\Controllers\Controller;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
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
        $usuario = $request->user();
        if ($usuario?->tieneRol('Paciente')) {
            $citaPropia = CitaMedica::findOrFail($id);
            if ((int) $citaPropia->id_paciente !== (int) $usuario->id_paciente) {
                AuditService::registrar(
                    $usuario->id_usuario,
                    'ACCESO_DENEGADO',
                    'citas_medicas',
                    $id,
                    [
                        'accion_ejecutada' => 'ACCESO_DENEGADO',
                        'motivo_justificacion' => 'Paciente intentó reprogramar una cita ajena',
                    ]
                );
                return $this->error('No está autorizado para reprogramar citas de otro paciente.', 403);
            }
        }

        $validated = $request->validate([
            'fecha_cita' => 'required|date_format:Y-m-d',
            'hora_cita' => 'required|string',
            'motivo_reprogramacion' => 'nullable|string|max:500',
        ]);

        try {
            $idUsuario = (int) ($usuario?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $citaActualizada = $this->reprogramarService->reprogramar($id, $validated, $idUsuario);

            return $this->successResponse($citaActualizada, 'Cita médica reprogramada exitosamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
