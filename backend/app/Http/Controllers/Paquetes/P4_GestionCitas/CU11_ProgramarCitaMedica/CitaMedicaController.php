<?php

namespace App\Http\Controllers\Paquetes\P4_GestionCitas\CU11_ProgramarCitaMedica;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P4_GestionCitas\CitaMedicaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class CitaMedicaController extends Controller
{
    use ApiResponse;

    protected CitaMedicaService $citaMedicaService;

    public function __construct(CitaMedicaService $citaMedicaService)
    {
        $this->citaMedicaService = $citaMedicaService;
    }

    /**
     * Listado de citas médicas.
     */
    public function index(Request $request): JsonResponse
    {
        $idPaciente = $request->query('id_paciente');
        $idMedico = $request->query('id_medico');
        $fecha = $request->query('fecha');

        $citas = $this->citaMedicaService->listar(
            $idPaciente ? (int) $idPaciente : null,
            $idMedico ? (int) $idMedico : null,
            $fecha
        );

        return $this->success($citas, 'Listado de citas médicas obtenido.');
    }

    /**
     * Consulta de cita por código único.
     */
    public function show(string $codigo): JsonResponse
    {
        try {
            $cita = $this->citaMedicaService->buscarPorCodigo($codigo);
            return $this->success($cita, 'Cita médica localizada.');
        } catch (Throwable $e) {
            return $this->error('Cita médica no encontrada.', 404);
        }
    }

    /**
     * CU11: Programación de cita médica con validación estricta de no superposición.
     */
    public function store(Request $request): JsonResponse
    {
        // Un paciente solo puede reservar para sí mismo, sin importar lo enviado en el formulario.
        $usuario = $request->user();
        if ($usuario?->tieneRol('Paciente')) {
            $request->merge(['id_paciente' => $usuario->id_paciente]);
        }

        $validated = $request->validate([
            'id_paciente' => 'required|integer|exists:pacientes,id_paciente',
            'id_medico' => 'required|integer|exists:medicos,id_medico',
            'id_especialidad' => 'required|integer|exists:especialidades,id_especialidad',
            'fecha_cita' => 'required|date_format:Y-m-d',
            'hora_cita' => 'required|string',
            'duracion_minutos' => 'nullable|integer|min:5|max:180',
            'motivo' => 'nullable|string|max:500',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $cita = $this->citaMedicaService->programar($validated, $operadorId);
            return $this->success($cita, 'Cita médica programada exitosamente con estado inicial Programada.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al programar la cita: ' . $e->getMessage(), 500);
        }
    }
}

