<?php

namespace App\Services\Paquetes\P3_GestionPacientes;

use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class PacienteService
{
    /**
     * Listar pacientes o filtrar por búsqueda (CI, nombre, correo).
     */
    public function listar(?string $termino = null, bool $soloActivos = false): Collection
    {
        $query = Paciente::query()->orderBy('id_paciente', 'desc');

        if ($soloActivos) {
            $query->where('estado', true);
        }

        if ($termino) {
            $query->where(function ($q) use ($termino) {
                $q->where('ci', 'like', "%{$termino}%")
                    ->orWhere('nombre_completo', 'like', "%{$termino}%")
                    ->orWhere('correo', 'like', "%{$termino}%")
                    ->orWhere('telefono', 'like', "%{$termino}%");
            });
        }

        // Devolvemos atributos incluyendo los marcados hidden para fines administrativos si es necesario
        return $query->get()->makeVisible([
            'ci',
            'fecha_nacimiento',
            'direccion_domicilio',
            'contacto_emergencia',
            'telefono_emergencia',
            'alergias',
            'informacion_medica_basica',
        ]);
    }

    /**
     * Registrar ficha de paciente con validación estricta de CI.
     */
    public function crear(array $datos, ?int $operadorId = null): Paciente
    {
        $ci = trim($datos['ci']);

        if (empty($ci)) {
            throw new InvalidArgumentException('El documento de identidad (CI) es estrictamente obligatorio.');
        }

        if (Paciente::where('ci', $ci)->exists()) {
            throw new InvalidArgumentException("Ya existe un paciente registrado con la cédula de identidad {$ci}.");
        }

        $paciente = Paciente::create([
            'ci' => $ci,
            'nombre_completo' => trim($datos['nombre_completo']),
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'genero' => $datos['genero'],
            'correo' => $datos['correo'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'direccion_domicilio' => $datos['direccion_domicilio'] ?? null,
            'contacto_emergencia' => $datos['contacto_emergencia'] ?? null,
            'telefono_emergencia' => $datos['telefono_emergencia'] ?? null,
            'tipo_paciente' => $datos['tipo_paciente'] ?? 'General',
            'alergias' => $datos['alergias'] ?? null,
            'informacion_medica_basica' => $datos['informacion_medica_basica'] ?? null,
            'estado' => $datos['estado'] ?? true,
        ]);

        AuditService::registrar(
            $operadorId,
            'CREAR_PACIENTE',
            'pacientes',
            $paciente->id_paciente,
            ['ci' => $paciente->ci, 'nombre' => $paciente->nombre_completo]
        );

        return $paciente->makeVisible(['ci', 'fecha_nacimiento']);
    }

    /**
     * Actualizar ficha de paciente preservando integridad de citas.
     */
    public function actualizar(int $idPaciente, array $datos, ?int $operadorId = null): Paciente
    {
        $paciente = Paciente::findOrFail($idPaciente);

        if (isset($datos['ci']) && trim($datos['ci']) !== $paciente->ci) {
            $ci = trim($datos['ci']);
            if (Paciente::where('ci', $ci)->where('id_paciente', '!=', $idPaciente)->exists()) {
                throw new InvalidArgumentException("La cédula de identidad {$ci} ya pertenece a otro paciente.");
            }
            $paciente->ci = $ci;
        }

        $campos = [
            'nombre_completo',
            'fecha_nacimiento',
            'genero',
            'correo',
            'telefono',
            'direccion_domicilio',
            'contacto_emergencia',
            'telefono_emergencia',
            'tipo_paciente',
            'alergias',
            'informacion_medica_basica',
            'estado'
        ];

        foreach ($campos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $paciente->{$campo} = $datos[$campo];
            }
        }

        $paciente->save();

        AuditService::registrar(
            $operadorId,
            'ACTUALIZAR_PACIENTE',
            'pacientes',
            $paciente->id_paciente,
            ['ci' => $paciente->ci, 'nombre' => $paciente->nombre_completo, 'estado' => $paciente->estado]
        );

        return $paciente->makeVisible(['ci', 'fecha_nacimiento']);
    }

    /**
     * Baja lógica del paciente sin borrar citas históricas.
     */
    public function toggleEstado(int $idPaciente, ?int $operadorId = null): Paciente
    {
        $paciente = Paciente::findOrFail($idPaciente);
        $paciente->estado = !$paciente->estado;
        $paciente->save();

        AuditService::registrar(
            $operadorId,
            $paciente->estado ? 'ACTIVAR_PACIENTE' : 'DESACTIVAR_PACIENTE',
            'pacientes',
            $paciente->id_paciente,
            ['nuevo_estado' => $paciente->estado]
        );

        return $paciente;
    }
}

