<?php

namespace App\Services\Paquetes\P7_ReportesEstadisticas;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use Illuminate\Support\Facades\DB;

class ReportesAdminService
{
    /**
     * Generar reporte administrativo agregado con métricas de citas, volúmenes y estados.
     */
    public function generarReporte(array $filtros): array
    {
        $fechaDesde = $filtros['fecha_desde'] ?? null;
        $fechaHasta = $filtros['fecha_hasta'] ?? null;
        $idEspecialidad = !empty($filtros['id_especialidad']) ? (int) $filtros['id_especialidad'] : null;
        $idMedico = !empty($filtros['id_medico']) ? (int) $filtros['id_medico'] : null;

        $citasQuery = DB::table('citas_medicas as c')
            ->join('estados_cita as e', 'c.id_estado_cita', '=', 'e.id_estado_cita')
            ->join('medicos as m', 'c.id_medico', '=', 'm.id_medico')
            ->join('especialidades as esp', 'c.id_especialidad', '=', 'esp.id_especialidad');

        if ($fechaDesde && $fechaHasta) {
            $citasQuery->whereBetween('c.fecha_cita', [$fechaDesde, $fechaHasta]);
        } elseif ($fechaDesde) {
            $citasQuery->where('c.fecha_cita', '>=', $fechaDesde);
        }

        if ($idEspecialidad) {
            $citasQuery->where('c.id_especialidad', $idEspecialidad);
        }

        if ($idMedico) {
            $citasQuery->where('c.id_medico', $idMedico);
        }

        // 1. Resumen por estado
        $porEstado = (clone $citasQuery)
            ->select('e.id_estado_cita', 'e.nombre_estado', DB::raw('count(c.id_cita) as total'))
            ->groupBy('e.id_estado_cita', 'e.nombre_estado')
            ->get();

        $totalCitas = $porEstado->sum('total');

        // Cálculo de porcentajes
        $estadosDetallados = $porEstado->map(function ($row) use ($totalCitas) {
            return [
                'id_estado_cita' => $row->id_estado_cita,
                'nombre_estado' => $row->nombre_estado,
                'total' => (int) $row->total,
                'porcentaje' => $totalCitas > 0 ? round(($row->total / $totalCitas) * 100, 2) : 0,
            ];
        });

        // 2. Volumen por Especialidad
        $porEspecialidad = (clone $citasQuery)
            ->select('esp.id_especialidad', 'esp.nombre_especialidad', DB::raw('count(c.id_cita) as total'))
            ->groupBy('esp.id_especialidad', 'esp.nombre_especialidad')
            ->orderByDesc('total')
            ->get();

        // 3. Volumen por Médico
        $porMedico = (clone $citasQuery)
            ->select('m.id_medico', 'm.nombre_completo', DB::raw('count(c.id_cita) as total'))
            ->groupBy('m.id_medico', 'm.nombre_completo')
            ->orderByDesc('total')
            ->get();

        // 4. Métricas clave (KPIs)
        $atendidas = $porEstado->firstWhere('nombre_estado', 'Atendida')?->total ?? 0;
        $canceladas = $porEstado->firstWhere('nombre_estado', 'Cancelada')?->total ?? 0;
        $inasistencias = $porEstado->firstWhere('nombre_estado', 'Inasistencia')?->total ?? 0;
        $programadas = $porEstado->firstWhere('nombre_estado', 'Programada')?->total ?? 0;
        $confirmadas = $porEstado->firstWhere('nombre_estado', 'Confirmada')?->total ?? 0;

        $tasaEfectividad = $totalCitas > 0 ? round(($atendidas / $totalCitas) * 100, 2) : 0;
        $tasaInasistencia = $totalCitas > 0 ? round(($inasistencias / $totalCitas) * 100, 2) : 0;
        $tasaCancelacion = $totalCitas > 0 ? round(($canceladas / $totalCitas) * 100, 2) : 0;

        return [
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'id_especialidad' => $idEspecialidad,
                'id_medico' => $idMedico,
            ],
            'kpis' => [
                'total_citas' => $totalCitas,
                'atendidas' => (int) $atendidas,
                'programadas' => (int) $programadas,
                'confirmadas' => (int) $confirmadas,
                'inasistencias' => (int) $inasistencias,
                'canceladas' => (int) $canceladas,
                'tasa_efectividad_pct' => $tasaEfectividad,
                'tasa_inasistencia_pct' => $tasaInasistencia,
                'tasa_cancelacion_pct' => $tasaCancelacion,
            ],
            'distribucion_estados' => $estadosDetallados,
            'por_especialidad' => $porEspecialidad,
            'por_medico' => $porMedico,
        ];
    }
}
