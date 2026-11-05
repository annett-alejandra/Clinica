<?php

namespace App\Http\Controllers\Paquetes\P7_ReportesEstadisticas\CU19_GenerarReportesAdministrativos;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P7_ReportesEstadisticas\ReportesAdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportesAdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportesAdminService $reportesService
    ) {}

    /**
     * Generar reporte administrativo global y filtrado.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'fecha_desde',
                'fecha_hasta',
                'id_especialidad',
                'id_medico',
            ]);

            $reporte = $this->reportesService->generarReporte($filtros);

            return $this->successResponse($reporte, 'Reporte administrativo generado con éxito.');
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
