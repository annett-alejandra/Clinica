<?php

namespace App\Services\Paquetes\P6_ConsultasHistoriales;

use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AgendaMedicaService
{
    /**
     * Consultar agenda médica con filtros dinámicos (médico, fecha o rango, especialidad, estado).
     */
    public function consultarAgenda(array $filtros, ?int $idUsuario = null): Collection
    {
        $query = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
            ->orderBy('fecha_cita', 'asc')
            ->orderBy('hora_cita', 'asc');

        // Si el usuario es médico, forzar a su propio id_medico a menos que sea admin
        if ($idUsuario) {
            $usuario = User::find($idUsuario);
            if ($usuario && $usuario->id_medico && !in_array($usuario->id_rol, [1, 2])) {
                $query->where('id_medico', $usuario->id_medico);
            }
        }

        if (!empty($filtros['id_medico'])) {
            $query->where('id_medico', (int) $filtros['id_medico']);
        }

        if (!empty($filtros['id_especialidad'])) {
            $query->where('id_especialidad', (int) $filtros['id_especialidad']);
        }

        if (!empty($filtros['id_estado_cita'])) {
            $query->where('id_estado_cita', (int) $filtros['id_estado_cita']);
        }

        // Filtro por fecha específica o rango
        if (!empty($filtros['fecha_cita'])) {
            $query->whereDate('fecha_cita', $filtros['fecha_cita']);
        } elseif (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $query->whereBetween('fecha_cita', [$filtros['fecha_desde'], $filtros['fecha_hasta']]);
        } elseif (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_cita', '>=', $filtros['fecha_desde']);
        }

        return $query->get();
    }
}
