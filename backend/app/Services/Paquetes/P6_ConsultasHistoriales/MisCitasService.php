<?php

namespace App\Services\Paquetes\P6_ConsultasHistoriales;

use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class MisCitasService
{
    /**
     * Obtener citas del paciente autenticado, separadas en Próximas Citas e Historial.
     */
    public function obtenerMisCitas(int $idUsuario, ?int $idPaciente = null): array
    {
        $usuario = User::with('paciente')->findOrFail($idUsuario);

        $pacienteId = $usuario->id_paciente ?? $idPaciente;
        if (!$pacienteId) {
            throw ValidationException::withMessages([
                'paciente' => ['La cuenta de usuario no tiene una ficha de paciente asociada.']
            ]);
        }

        $hoy = Carbon::today()->format('Y-m-d');
        $ahora = Carbon::now()->format('H:i:s');

        $citasBase = CitaMedica::with(['medico', 'especialidad', 'estado'])
            ->where('id_paciente', $pacienteId);

        // Próximas citas: fecha > hoy O (fecha == hoy Y hora >= ahora), y en estado activa (1 o 2)
        $proximas = (clone $citasBase)
            ->where(function ($q) use ($hoy, $ahora) {
                $q->whereDate('fecha_cita', '>', $hoy)
                  ->orWhere(function ($q2) use ($hoy, $ahora) {
                      $q2->whereDate('fecha_cita', '=', $hoy)
                         ->where('hora_cita', '>=', $ahora);
                  });
            })
            ->whereIn('id_estado_cita', [1, 2])
            ->orderBy('fecha_cita', 'asc')
            ->orderBy('hora_cita', 'asc')
            ->get();

        // Historial: citas pasadas O con estados concluidos (3 = Atendida, 4 = Cancelada, 5 = Inasistencia)
        $historial = (clone $citasBase)
            ->where(function ($q) use ($hoy, $ahora) {
                $q->whereIn('id_estado_cita', [3, 4, 5])
                  ->orWhere(function ($q2) use ($hoy, $ahora) {
                      $q2->whereDate('fecha_cita', '<', $hoy)
                         ->orWhere(function ($q3) use ($hoy, $ahora) {
                             $q3->whereDate('fecha_cita', '=', $hoy)
                                ->where('hora_cita', '<', $ahora);
                         });
                  });
            })
            ->orderBy('fecha_cita', 'desc')
            ->orderBy('hora_cita', 'desc')
            ->get();

        return [
            'paciente' => $usuario->paciente,
            'proximas_citas' => $proximas,
            'historial_citas' => $historial,
            'total_proximas' => $proximas->count(),
            'total_historial' => $historial->count(),
        ];
    }
}
