<?php

namespace App\Http\Controllers\Paquetes\P2_PersonalMedico\CU06_GestionarHorariosAtencion;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P2_PersonalMedico\HorarioService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class HorariosController extends Controller
{
    use ApiResponse;

    protected HorarioService $horarioService;

    public function __construct(HorarioService $horarioService)
    {
        $this->horarioService = $horarioService;
    }

    /**
     * Listado de horarios de atención médica.
     */
    public function index(Request $request): JsonResponse
    {
        $idMedico = $request->query('id_medico');
        $horarios = $this->horarioService->listar($idMedico ? (int) $idMedico : null);
        return $this->success($horarios, 'Horarios de atención obtenidos.');
    }

    /**
     * Registro de turno y prevención de superposición.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_medico' => 'required|integer|exists:medicos,id_medico',
            'dia_semana' => 'required|integer|min:1|max:7',
            'hora_inicio' => 'required|string',
            'hora_fin' => 'required|string',
            'duracion_cita_minutos' => 'required|integer|min:5|max:240',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $horario = $this->horarioService->crear($validated, $operadorId);
            return $this->success($horario, 'Horario de atención configurado exitosamente.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al registrar horario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminación de horario.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $this->horarioService->eliminar($id, $operadorId);
            return $this->success(null, 'Horario eliminado con éxito.');
        } catch (Throwable $e) {
            return $this->error('Error al eliminar horario: ' . $e->getMessage(), 500);
        }
    }
}

