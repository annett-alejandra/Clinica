<?php

namespace App\Services\Paquetes\P1_SeguridadAdministracion;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EspecialidadService
{
    /**
     * Listar especialidades (opcionalmente solo activas).
     */
    public function listar(bool $soloActivas = false): Collection
    {
        $query = Especialidad::query()->withCount('medicos')->orderBy('nombre_especialidad', 'asc');
        if ($soloActivas) {
            $query->where('estado', true);
        }
        return $query->get();
    }

    /**
     * Registrar una nueva especialidad médica validando nombre normalizado.
     */
    public function crear(array $datos, ?int $operadorId = null): Especialidad
    {
        $nombreNormalizado = trim($datos['nombre_especialidad']);

        // Verificar duplicados sin distinguir mayúsculas/minúsculas
        if (Especialidad::whereRaw('LOWER(nombre_especialidad) = ?', [Str::lower($nombreNormalizado)])->exists()) {
            throw new InvalidArgumentException("La especialidad médica '{$nombreNormalizado}' ya está registrada.");
        }

        $especialidad = Especialidad::create([
            'nombre_especialidad' => $nombreNormalizado,
            'descripcion' => $datos['descripcion'] ?? null,
            'estado' => $datos['estado'] ?? true,
        ]);

        AuditService::registrar(
            $operadorId,
            'CREAR_ESPECIALIDAD',
            'especialidades',
            $especialidad->id_especialidad,
            ['nombre_especialidad' => $especialidad->nombre_especialidad]
        );

        return $especialidad;
    }

    /**
     * Modificar datos de una especialidad.
     */
    public function actualizar(int $idEspecialidad, array $datos, ?int $operadorId = null): Especialidad
    {
        $especialidad = Especialidad::findOrFail($idEspecialidad);

        if (isset($datos['nombre_especialidad'])) {
            $nombreNormalizado = trim($datos['nombre_especialidad']);
            $existe = Especialidad::whereRaw('LOWER(nombre_especialidad) = ?', [Str::lower($nombreNormalizado)])
                ->where('id_especialidad', '!=', $idEspecialidad)
                ->exists();

            if ($existe) {
                throw new InvalidArgumentException("Ya existe otra especialidad médica con el nombre '{$nombreNormalizado}'.");
            }
            $especialidad->nombre_especialidad = $nombreNormalizado;
        }

        if (array_key_exists('descripcion', $datos)) {
            $especialidad->descripcion = $datos['descripcion'];
        }

        if (isset($datos['estado'])) {
            $especialidad->estado = (bool) $datos['estado'];
        }

        $especialidad->save();

        AuditService::registrar(
            $operadorId,
            'ACTUALIZAR_ESPECIALIDAD',
            'especialidades',
            $especialidad->id_especialidad,
            ['nombre_especialidad' => $especialidad->nombre_especialidad, 'estado' => $especialidad->estado]
        );

        return $especialidad;
    }

    /**
     * Alternar estado activo/inactivo (baja lógica).
     */
    public function toggleEstado(int $idEspecialidad, ?int $operadorId = null): Especialidad
    {
        $especialidad = Especialidad::findOrFail($idEspecialidad);
        $especialidad->estado = !$especialidad->estado;
        $especialidad->save();

        AuditService::registrar(
            $operadorId,
            $especialidad->estado ? 'ACTIVAR_ESPECIALIDAD' : 'DESACTIVAR_ESPECIALIDAD',
            'especialidades',
            $especialidad->id_especialidad,
            ['nuevo_estado' => $especialidad->estado]
        );

        return $especialidad;
    }
}

