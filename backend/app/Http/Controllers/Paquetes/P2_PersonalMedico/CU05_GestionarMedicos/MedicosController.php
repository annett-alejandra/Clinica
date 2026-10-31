<?php

namespace App\Http\Controllers\Paquetes\P2_PersonalMedico\CU05_GestionarMedicos;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P2_PersonalMedico\MedicoService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class MedicosController extends Controller
{
    use ApiResponse;

    protected MedicoService $medicoService;

    public function __construct(MedicoService $medicoService)
    {
        $this->medicoService = $medicoService;
    }

    /**
     * Listado de médicos.
     */
    public function index(Request $request): JsonResponse
    {
        $soloActivos = $request->boolean('solo_activos', false);
        $medicos = $this->medicoService->listar($soloActivos);
        return $this->success($medicos, 'Médicos obtenidos con éxito.');
    }

    /**
     * Registro de nuevo médico con especialidades.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'registro_profesional' => 'required|string|max:100',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'especialidades' => 'nullable|array',
            'especialidades.*' => 'integer|exists:especialidades,id_especialidad',
            'estado' => 'nullable|boolean',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $medico = $this->medicoService->crear($validated, $operadorId);
            return $this->success($medico, 'Médico registrado exitosamente.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al registrar al médico: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualización de médico.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nombre_completo' => 'sometimes|string|max:255',
            'registro_profesional' => 'sometimes|string|max:100',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'especialidades' => 'nullable|array',
            'especialidades.*' => 'integer|exists:especialidades,id_especialidad',
            'estado' => 'sometimes|boolean',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $medico = $this->medicoService->actualizar($id, $validated, $operadorId);
            return $this->success($medico, 'Médico actualizado exitosamente.');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al actualizar médico: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Alternar estado activo/inactivo.
     */
    public function toggleEstado(Request $request, int $id): JsonResponse
    {
        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $medico = $this->medicoService->toggleEstado($id, $operadorId);
            return $this->success($medico, 'Estado del médico actualizado.');
        } catch (Throwable $e) {
            return $this->error('Error al modificar estado: ' . $e->getMessage(), 500);
        }
    }
}

