<?php

namespace App\Services\Paquetes\P2_PersonalMedico;

use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MedicoService
{
    /**
     * Listar médicos con sus especialidades y horarios.
     */
    public function listar(bool $soloActivos = false): Collection
    {
        $query = Medico::query()
            ->with(['especialidades', 'horariosAtencion'])
            ->orderBy('nombre_completo', 'asc');

        if ($soloActivos) {
            $query->where('estado', true);
        }

        return $query->get();
    }

    /**
     * Registrar un médico y asociar sus especialidades.
     */
    public function crear(array $datos, ?int $operadorId = null): Medico
    {
        if (Medico::where('registro_profesional', $datos['registro_profesional'])->exists()) {
            throw new InvalidArgumentException("El registro profesional '{$datos['registro_profesional']}' ya está asignado a otro médico.");
        }

        return DB::transaction(function () use ($datos, $operadorId) {
            $medico = Medico::create([
                'nombre_completo' => $datos['nombre_completo'],
                'registro_profesional' => $datos['registro_profesional'],
                'correo' => $datos['correo'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'estado' => $datos['estado'] ?? true,
            ]);

            // Asignar especialidades en la tabla intermedia medicos_especialidades
            if (!empty($datos['especialidades'])) {
                $medico->especialidades()->sync($datos['especialidades']);
            }

            AuditService::registrar(
                $operadorId,
                'CREAR_MEDICO',
                'medicos',
                $medico->id_medico,
                [
                    'nombre' => $medico->nombre_completo,
                    'registro' => $medico->registro_profesional,
                    'especialidades' => $datos['especialidades'] ?? []
                ]
            );

            return $medico->load(['especialidades', 'horariosAtencion']);
        });
    }

    /**
     * Actualizar perfil de médico y sincronizar especialidades.
     */
    public function actualizar(int $idMedico, array $datos, ?int $operadorId = null): Medico
    {
        $medico = Medico::findOrFail($idMedico);

        if (isset($datos['registro_profesional']) && $datos['registro_profesional'] !== $medico->registro_profesional) {
            if (Medico::where('registro_profesional', $datos['registro_profesional'])->where('id_medico', '!=', $idMedico)->exists()) {
                throw new InvalidArgumentException("El registro profesional '{$datos['registro_profesional']}' ya está en uso.");
            }
            $medico->registro_profesional = $datos['registro_profesional'];
        }

        return DB::transaction(function () use ($medico, $datos, $operadorId) {
            if (isset($datos['nombre_completo'])) {
                $medico->nombre_completo = $datos['nombre_completo'];
            }
            if (array_key_exists('correo', $datos)) {
                $medico->correo = $datos['correo'];
            }
            if (array_key_exists('telefono', $datos)) {
                $medico->telefono = $datos['telefono'];
            }
            if (isset($datos['estado'])) {
                $medico->estado = (bool) $datos['estado'];
            }

            $medico->save();

            if (isset($datos['especialidades'])) {
                $medico->especialidades()->sync($datos['especialidades']);
            }

            AuditService::registrar(
                $operadorId,
                'ACTUALIZAR_MEDICO',
                'medicos',
                $medico->id_medico,
                ['nombre' => $medico->nombre_completo, 'estado' => $medico->estado]
            );

            return $medico->load(['especialidades', 'horariosAtencion']);
        });
    }

    /**
     * Alternar estado activo/inactivo del médico.
     */
    public function toggleEstado(int $idMedico, ?int $operadorId = null): Medico
    {
        $medico = Medico::findOrFail($idMedico);
        $medico->estado = !$medico->estado;
        $medico->save();

        AuditService::registrar(
            $operadorId,
            $medico->estado ? 'ACTIVAR_MEDICO' : 'DESACTIVAR_MEDICO',
            'medicos',
            $medico->id_medico,
            ['nuevo_estado' => $medico->estado]
        );

        return $medico->load(['especialidades', 'horariosAtencion']);
    }
}

