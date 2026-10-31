<?php

namespace App\Http\Controllers\Paquetes\P3_GestionPacientes\CU07_GestionarPacientes;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P3_GestionPacientes\PacienteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class PacientesController extends Controller
{
    use ApiResponse;

    protected PacienteService $pacienteService;

    public function __construct(PacienteService $pacienteService)
    {
        $this->pacienteService = $pacienteService;
    }

    /**
     * Listado y búsqueda de pacientes.
     */
    public function index(Request $request): JsonResponse
    {
        $termino = $request->query('q');
        $soloActivos = $request->boolean('solo_activos', false);
        $pacientes = $this->pacienteService->listar($termino, $soloActivos);
        return $this->success($pacientes, 'Pacientes obtenidos con éxito.');
    }

    /**
     * Registro de nuevo paciente desde recepción.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ci' => 'required|string|max:50',
            'nombre_completo' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|string|max:50',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion_domicilio' => 'nullable|string|max:255',
            'contacto_emergencia' => 'nullable|string|max:255',
            'telefono_emergencia' => 'nullable|string|max:50',
            'tipo_paciente' => 'nullable|string|max:100',
            'alergias' => 'nullable|string',
            'informacion_medica_basica' => 'nullable|string',
            'estado' => 'nullable|boolean',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $paciente = $this->pacienteService->crear($validated, $operadorId);
            return $this->success($paciente, 'Paciente registrado con éxito.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al registrar el paciente: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualización de datos del paciente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'ci' => 'sometimes|string|max:50',
            'nombre_completo' => 'sometimes|string|max:255',
            'fecha_nacimiento' => 'sometimes|date',
            'genero' => 'sometimes|string|max:50',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion_domicilio' => 'nullable|string|max:255',
            'contacto_emergencia' => 'nullable|string|max:255',
            'telefono_emergencia' => 'nullable|string|max:50',
            'tipo_paciente' => 'nullable|string|max:100',
            'alergias' => 'nullable|string',
            'informacion_medica_basica' => 'nullable|string',
            'estado' => 'sometimes|boolean',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $paciente = $this->pacienteService->actualizar($id, $validated, $operadorId);
            return $this->success($paciente, 'Paciente actualizado con éxito.');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al actualizar paciente: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Desactivación o reactivación lógica.
     */
    public function toggleEstado(Request $request, int $id): JsonResponse
    {
        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $paciente = $this->pacienteService->toggleEstado($id, $operadorId);
            return $this->success($paciente, 'Estado del paciente modificado con éxito.');
        } catch (Throwable $e) {
            return $this->error('Error al modificar estado: ' . $e->getMessage(), 500);
        }
    }
}

