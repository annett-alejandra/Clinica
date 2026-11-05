<?php

namespace App\Http\Controllers\Paquetes\P3_GestionPacientes\CU08_GestionarPerfilPaciente;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P3_GestionPacientes\PerfilPacienteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PerfilPacienteController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PerfilPacienteService $perfilService
    ) {}

    /**
     * Obtener el perfil del paciente logueado.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $idPaciente = $request->query('id_paciente') ? (int) $request->query('id_paciente') : null;

            $perfil = $this->perfilService->obtenerPerfil($idUsuario, $idPaciente);

            return $this->successResponse($perfil, 'Perfil de paciente obtenido correctamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Actualizar los datos de contacto y emergencia.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_paciente' => 'nullable|integer|exists:pacientes,id_paciente',
            'telefono' => 'required|string|max:30',
            'correo' => 'required|email|max:150',
            'direccion_domicilio' => 'nullable|string|max:255',
            'contacto_emergencia' => 'nullable|string|max:150',
            'telefono_emergencia' => 'nullable|string|max:30',
        ]);

        try {
            $idUsuario = (int) ($request->user()?->id_usuario ?? $request->header('X-User-Id') ?? 1);
            $pacienteActualizado = $this->perfilService->actualizarPerfil($idUsuario, $validated);

            return $this->successResponse($pacienteActualizado, 'Perfil actualizado exitosamente.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
