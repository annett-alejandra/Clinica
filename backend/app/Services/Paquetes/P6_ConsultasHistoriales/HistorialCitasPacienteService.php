<?php

namespace App\Services\Paquetes\P6_ConsultasHistoriales;

use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use Illuminate\Validation\ValidationException;

class HistorialCitasPacienteService
{
    /**
     * Consultar expediente de citas por ID de paciente o CI.
     */
    public function obtenerHistorial(string|int $identificador): array
    {
        $paciente = Paciente::where('id_paciente', is_numeric($identificador) ? (int) $identificador : 0)
            ->orWhere('ci', (string) $identificador)
            ->first();

        if (!$paciente) {
            throw ValidationException::withMessages([
                'paciente' => ["No se encontró ningún paciente con el identificador o cédula '{$identificador}'."]
            ]);
        }

        $citas = CitaMedica::with(['medico', 'especialidad', 'estado', 'historialCambios'])
            ->where('id_paciente', $paciente->id_paciente)
            ->orderBy('fecha_cita', 'desc')
            ->orderBy('hora_cita', 'desc')
            ->get();

        return [
            'paciente' => $paciente,
            'total_citas' => $citas->count(),
            'citas' => $citas,
        ];
    }
}
