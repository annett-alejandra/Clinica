<?php

namespace App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU04_GestionarEspecialidadesMedicas;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P1_SeguridadAdministracion\EspecialidadService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class EspecialidadController extends Controller
{
    use ApiResponse;

    protected EspecialidadService $especialidadService;

    public function __construct(EspecialidadService $especialidadService)
    {
        $this->especialidadService = $especialidadService;
    }

    /**
     * Listado de especialidades médicas.
     */
    public function index(Request $request): JsonResponse
    {
        $soloActivas = $request->boolean('solo_activas', false);
        $especialidades = $this->especialidadService->listar($soloActivas);
        return $this->success($especialidades, 'Especialidades obtenidas con éxito.');
    }

    /**
     * Registro de especialidad médica.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre_especialidad' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'estado' => 'nullable|boolean',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $especialidad = $this->especialidadService->crear($validated, $operadorId);
            return $this->success($especialidad, 'Especialidad médica registrada con éxito.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al registrar la especialidad: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualización de especialidad médica.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nombre_especialidad' => 'sometimes|string|max:150',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|boolean',
        ]);

        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $especialidad = $this->especialidadService->actualizar($id, $validated, $operadorId);
            return $this->success($especialidad, 'Especialidad actualizada con éxito.');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error al actualizar la especialidad: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Alternar estado (activar / desactivar).
     */
    public function toggleEstado(Request $request, int $id): JsonResponse
    {
        try {
            $operadorId = $request->input('operador_id') ?? auth()->id();
            $especialidad = $this->especialidadService->toggleEstado($id, $operadorId);
            return $this->success($especialidad, 'Estado de la especialidad modificado con éxito.');
        } catch (Throwable $e) {
            return $this->error('Error al modificar estado: ' . $e->getMessage(), 500);
        }
    }
}

