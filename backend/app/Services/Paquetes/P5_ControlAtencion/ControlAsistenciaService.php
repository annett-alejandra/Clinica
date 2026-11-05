<?php

namespace App\Services\Paquetes\P5_ControlAtencion;

use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\Paquetes\P4_GestionCitas\HistorialCambioCita;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ControlAsistenciaService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Confirmar una cita (cuando el paciente avisa que sí asistirá).
     * Estado: 1 (Programada) -> 2 (Confirmada)
     */
    public function confirmarCita(int $idCita, ?int $idUsuario = null): CitaMedica
    {
        $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($idCita);

        if ((int) $cita->id_estado_cita !== 1) {
            throw ValidationException::withMessages([
                'id_estado_cita' => ['Solo se pueden confirmar citas en estado Programada.']
            ]);
        }

        return DB::transaction(function () use ($cita, $idUsuario) {
            $cita->id_estado_cita = 2; // Confirmada
            $cita->save();

            HistorialCambioCita::create([
                'id_cita' => $cita->id_cita,
                'id_usuario' => $idUsuario,
                'tipo_cambio' => 'CONFIRMACION',
                'estado_anterior' => 1,
                'estado_nuevo' => 2,
                'observacion' => 'Confirmación de asistencia recibida por recepción',
            ]);

            $this->auditService->registrar(
                idUsuario: $idUsuario ?? 1,
                accion: 'CONFIRMAR_CITA',
                tablaAfectada: 'citas_medicas',
                idRegistroAfectado: $cita->id_cita,
                detalle: ['codigo_unico' => $cita->codigo_unico]
            );

            return $cita->fresh(['paciente', 'medico', 'especialidad', 'estado']);
        });
    }

    /**
     * Registrar la llegada física del paciente a la clínica.
     * Actualiza fecha_hora_llegada y asegura estado Confirmada.
     */
    public function registrarLlegada(int $idCita, ?int $idUsuario = null): CitaMedica
    {
        $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($idCita);

        if (!in_array((int) $cita->id_estado_cita, [1, 2])) {
            throw ValidationException::withMessages([
                'id_estado_cita' => ['Solo se puede registrar la llegada de pacientes con citas Programadas o Confirmadas.']
            ]);
        }

        return DB::transaction(function () use ($cita, $idUsuario) {
            $estadoAnterior = (int) $cita->id_estado_cita;
            $cita->id_estado_cita = 2; // Asegurar confirmada
            $cita->fecha_hora_llegada = Carbon::now();
            $cita->save();

            HistorialCambioCita::create([
                'id_cita' => $cita->id_cita,
                'id_usuario' => $idUsuario,
                'tipo_cambio' => 'LLEGADA_RECEPCION',
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 2,
                'observacion' => 'Paciente presente en sala de espera a las ' . Carbon::now()->format('H:i'),
            ]);

            $this->auditService->registrar(
                idUsuario: $idUsuario ?? 1,
                accion: 'REGISTRAR_LLEGADA_PACIENTE',
                tablaAfectada: 'citas_medicas',
                idRegistroAfectado: $cita->id_cita,
                detalle: [
                    'codigo_unico' => $cita->codigo_unico,
                    'fecha_hora_llegada' => $cita->fecha_hora_llegada->toIso8601String(),
                ]
            );

            return $cita->fresh(['paciente', 'medico', 'especialidad', 'estado']);
        });
    }

    /**
     * Marcar inasistencia del paciente.
     * Estado: 1 o 2 -> 5 (Inasistencia)
     */
    public function marcarInasistencia(int $idCita, ?string $motivo = null, ?int $idUsuario = null): CitaMedica
    {
        $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($idCita);

        if (!in_array((int) $cita->id_estado_cita, [1, 2])) {
            throw ValidationException::withMessages([
                'id_estado_cita' => ['Solo se puede marcar inasistencia en citas Programadas o Confirmadas.']
            ]);
        }

        return DB::transaction(function () use ($cita, $motivo, $idUsuario) {
            $estadoAnterior = (int) $cita->id_estado_cita;
            $cita->id_estado_cita = 5; // Inasistencia
            $cita->save();

            HistorialCambioCita::create([
                'id_cita' => $cita->id_cita,
                'id_usuario' => $idUsuario,
                'tipo_cambio' => 'INASISTENCIA',
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 5,
                'observacion' => $motivo ?: 'Paciente no se presentó en la hora agendada',
            ]);

            $this->auditService->registrar(
                idUsuario: $idUsuario ?? 1,
                accion: 'MARCAR_INASISTENCIA',
                tablaAfectada: 'citas_medicas',
                idRegistroAfectado: $cita->id_cita,
                detalle: ['codigo_unico' => $cita->codigo_unico, 'motivo' => $motivo]
            );

            return $cita->fresh(['paciente', 'medico', 'especialidad', 'estado']);
        });
    }
}
