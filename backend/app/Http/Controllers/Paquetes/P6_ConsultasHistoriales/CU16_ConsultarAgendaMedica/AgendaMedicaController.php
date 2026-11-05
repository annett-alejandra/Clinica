<?php

namespace App\Http\Controllers\Paquetes\P6_ConsultasHistoriales\CU16_ConsultarAgendaMedica;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P6_ConsultasHistoriales\AgendaMedicaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaMedicaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AgendaMedicaService $agendaService
    ) {}

    /**
     * Consultar agenda médica con filtros dinámicos.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'id_medico',
                'id_especialidad',
                'id_estado_cita',
                'fecha_cita',
                'fecha_desde',
                'fecha_hasta',
            ]);

            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $citas = $this->agendaService->consultarAgenda($filtros, $idUsuario);

            return $this->successResponse($citas, 'Agenda médica consultada exitosamente.');
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
