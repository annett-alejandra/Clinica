<?php

namespace App\Http\Controllers\Paquetes\P3_GestionPacientes\CU09_RegistrarCuentaPaciente;

use App\Http\Controllers\Controller;
use App\Services\Paquetes\P3_GestionPacientes\RegistroPacienteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class RegistroPacienteController extends Controller
{
    use ApiResponse;

    protected RegistroPacienteService $registroService;

    public function __construct(RegistroPacienteService $registroService)
    {
        $this->registroService = $registroService;
    }

    /**
     * CU09: Auto-registro público de nuevo paciente (Registrar Cuenta Paciente).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ci' => 'required|string|max:50',
            'nombre_completo' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|string|max:50',
            'correo' => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'nombre_usuario' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:50',
            'direccion_domicilio' => 'nullable|string|max:255',
        ]);

        try {
            $resultado = $this->registroService->registrar($validated);
            return $this->success($resultado, 'Registro de paciente completado con éxito.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error('Error durante el auto-registro: ' . $e->getMessage(), 500);
        }
    }
}

