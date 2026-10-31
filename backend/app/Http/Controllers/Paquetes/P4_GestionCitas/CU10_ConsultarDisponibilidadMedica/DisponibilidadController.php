<?php

namespace App\Http\Controllers\Paquetes\P4_GestionCitas\CU10_ConsultarDisponibilidadMedica;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P4_GestionCitas\DisponibilidadService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class DisponibilidadController extends Controller
{
    use ApiResponse;

    protected DisponibilidadService $disponibilidadService;

    public function __construct(DisponibilidadService $disponibilidadService)
    {
        $this->disponibilidadService = $disponibilidadService;
    }

    /**
     * CU10: Consultar oferta de horarios y cupos libres para citas.
     */
    public function consultar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_especialidad' => 'required|integer|exists:especialidades,id_especialidad',
            'id_medico' => 'nullable|integer|exists:medicos,id_medico',
            'fecha' => 'required|date_format:Y-m-d',
        ]);

        try {
            $disponibilidad = $this->disponibilidadService->consultar(
                (int) $validated['id_especialidad'],
                isset($validated['id_medico']) ? (int) $validated['id_medico'] : null,
                $validated['fecha']
            );

            return $this->success($disponibilidad, 'Disponibilidad médica consultada exitosamente.');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al calcular disponibilidad: ' . $e->getMessage(), 500);
        }
    }
}

