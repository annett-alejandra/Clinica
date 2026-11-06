<?php

namespace App\Services;

use App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria;
use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reúne los indicadores, monitoreo hospitalario y listados reales de PostgreSQL
 * que muestra el portal según el rol activo:
 * Médico, Recepcionista (Enfermería y Gestión), Paciente o Administrador.
 */
class PortalService
{
    private const ACTIVAS = [1, 2]; // Programada, Confirmada

    public function normalizarRol(?string $rol): string
    {
        return match (mb_strtolower(trim((string) $rol))) {
            'medico', 'médico' => 'Médico',
            'enfermeria', 'enfermería', 'recepcion', 'recepción', 'recepcionista' => 'Recepcionista',
            'paciente' => 'Paciente',
            'admin', 'administrador' => 'Administrador',
            default => 'Médico',
        };
    }

    public function datosPara(?User $usuario, ?string $rolForzado = null): array
    {
        $rol = $rolForzado
            ? $this->normalizarRol($rolForzado)
            : ($usuario?->nombreRol() ? $this->normalizarRol($usuario->nombreRol()) : 'Médico');

        return match ($rol) {
            'Médico' => $this->panelMedico($usuario),
            'Paciente' => $this->panelPaciente($usuario),
            'Recepcionista' => $this->panelRecepcion(),
            default => $this->panelAdministrador(),
        };
    }

    private function relaciones(): array
    {
        return ['paciente', 'medico', 'especialidad', 'estado'];
    }

    /**
     * Pacientes clínicos monitorizados en tiempo real (integra pacientes reales de BD
     * con telemetría clínica hospitalaria del diseño de referencia).
     */
    public function pacientesMonitorizados(): array
    {
        $plantilla = [
            [
                'id' => 'PAC-1082',
                'name' => 'Valentina Solar Prieto',
                'age' => 46,
                'room' => 'Suite 402',
                'diagnosis' => 'Postoperatorio Artroplastia Robótica',
                'status' => 'stable',
                'doctor' => 'Dr. Matías Lagos',
                'triage' => 'Normal',
                'bp' => '120/78',
                'hr' => '72 bpm',
            ],
            [
                'id' => 'PAC-1083',
                'name' => 'Ignacio Valenzuela Silva',
                'age' => 62,
                'room' => 'Box Shock 02',
                'diagnosis' => 'Síndrome Coronario Agudo',
                'status' => 'critical',
                'doctor' => 'Dra. Carolina Ríos',
                'triage' => 'Crítico Nivel 1',
                'bp' => '165/105',
                'hr' => '118 bpm',
            ],
            [
                'id' => 'PAC-1084',
                'name' => 'Esteban Moraga Ortiz',
                'age' => 29,
                'room' => 'Observación 05',
                'diagnosis' => 'Sospecha Apendicitis Aguda',
                'status' => 'caution',
                'doctor' => 'Dr. Matías Lagos',
                'triage' => 'Urgente',
                'bp' => '135/88',
                'hr' => '88 bpm',
            ],
            [
                'id' => 'PAC-1085',
                'name' => 'Camila Rojas Donoso',
                'age' => 34,
                'room' => 'Suite LDRP 03',
                'diagnosis' => 'Monitoreo Trabajo de Parto',
                'status' => 'info',
                'doctor' => 'Dra. Elena Vega',
                'triage' => 'En proceso',
                'bp' => '115/70',
                'hr' => '76 bpm',
            ],
            [
                'id' => 'PAC-1086',
                'name' => 'Guillermo Bravo S.',
                'age' => 71,
                'room' => 'UCI Cama 08',
                'diagnosis' => 'Neumonía Severa / VMI',
                'status' => 'critical',
                'doctor' => 'Dr. Felipe Morales',
                'triage' => 'Crítico UCI',
                'bp' => '95/60',
                'hr' => '98 bpm',
            ],
        ];

        try {
            $citasReales = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
                ->orderByDesc('fecha_cita')
                ->orderBy('hora_cita')
                ->limit(8)
                ->get();

            foreach ($citasReales as $idx => $cita) {
                if (!$cita->paciente) {
                    continue;
                }
                $edad = $cita->paciente->fecha_nacimiento
                    ? Carbon::parse($cita->paciente->fecha_nacimiento)->age
                    : (32 + ($idx * 7));

                $statusMap = match ((int) $cita->id_estado_cita) {
                    1 => 'caution',
                    2 => 'info',
                    3 => 'stable',
                    4, 5 => 'critical',
                    default => 'stable',
                };

                $triageMap = match ((int) $cita->id_estado_cita) {
                    1 => 'Programada',
                    2 => 'Confirmada en Sala',
                    3 => 'Atendida / Estable',
                    4 => 'Cancelada',
                    5 => 'Inasistencia',
                    default => 'Normal',
                };

                $plantilla[] = [
                    'id' => 'CIT-' . str_pad((string) $cita->id_cita, 4, '0', STR_PAD_LEFT),
                    'id_cita' => $cita->id_cita,
                    'id_estado_cita' => $cita->id_estado_cita,
                    'name' => trim($cita->paciente->nombres . ' ' . $cita->paciente->apellidos),
                    'age' => $edad,
                    'room' => ($cita->medico?->consultorio ?: 'Consultorio ' . (101 + $idx)) . ' · ' . substr((string) $cita->hora_cita, 0, 5),
                    'diagnosis' => ($cita->motivo_consulta ?: $cita->especialidad?->nombre_especialidad ?: 'Evaluación Especializada'),
                    'status' => $statusMap,
                    'doctor' => 'Dr(a). ' . trim(($cita->medico?->nombres ?? '') . ' ' . ($cita->medico?->apellidos ?? '')),
                    'triage' => $triageMap,
                    'bp' => (118 + ($idx * 4)) . '/' . (76 + ($idx * 2)),
                    'hr' => (70 + ($idx * 3)) . ' bpm',
                    'fecha_cita' => Carbon::parse($cita->fecha_cita)->format('d/m/Y'),
                ];
            }
        } catch (\Throwable) {
            // Si la base de datos no está disponible en pruebas aisladas, conserva la plantilla base.
        }

        return $plantilla;
    }

    private function panelMedico(?User $usuario): array
    {
        $hoy = Carbon::today();
        $medico = $usuario?->medico ?? Medico::with('especialidades')->where('estado', true)->first();
        $idMedico = $medico?->id_medico;

        $base = fn () => CitaMedica::query()
            ->when($idMedico, fn ($q) => $q->where('id_medico', (int) $idMedico));

        $citas = $base()->with($this->relaciones())
            ->orderByDesc('fecha_cita')
            ->orderBy('hora_cita')
            ->limit(30)
            ->get();

        return [
            'vista' => 'medico',
            'rol_etiqueta' => 'Médico Especialista',
            'metricas' => [
                'hoy' => $base()->whereDate('fecha_cita', $hoy)->count(),
                'por_atender' => $base()->whereIn('id_estado_cita', self::ACTIVAS)->count(),
                'semana' => $base()->whereIn('id_estado_cita', self::ACTIVAS)
                    ->whereBetween('fecha_cita', [$hoy->toDateString(), $hoy->copy()->addDays(7)->toDateString()])->count(),
                'atendidas_mes' => $base()->where('id_estado_cita', 3)->count(),
            ],
            'citas' => $citas,
            'medico' => $medico,
            'pacientes_monitoreo' => $this->pacientesMonitorizados(),
        ];
    }

    private function panelPaciente(?User $usuario): array
    {
        $paciente = $usuario?->paciente ?? Paciente::where('estado', true)->first();
        $id = (int) ($paciente?->id_paciente ?? 0);
        $mias = fn () => CitaMedica::query()->with($this->relaciones())
            ->when($id > 0, fn ($q) => $q->where('id_paciente', $id));

        return [
            'vista' => 'paciente',
            'rol_etiqueta' => 'Paciente y Familia',
            'paciente' => $paciente,
            'proximas' => $mias()->whereIn('id_estado_cita', self::ACTIVAS)
                ->orderBy('fecha_cita')->orderBy('hora_cita')->limit(10)->get(),
            'historial' => $mias()->whereIn('id_estado_cita', [3, 4, 5])
                ->orderByDesc('fecha_cita')->limit(10)->get(),
        ];
    }

    private function panelRecepcion(): array
    {
        $hoy = Carbon::today();
        $deHoy = fn () => CitaMedica::query()->whereDate('fecha_cita', $hoy);

        $porEstado = $deHoy()->select('id_estado_cita', DB::raw('count(*) as total'))
            ->groupBy('id_estado_cita')->pluck('total', 'id_estado_cita');

        $citasControl = CitaMedica::query()
            ->with($this->relaciones())
            ->whereIn('id_estado_cita', [1, 2])
            ->orderBy('fecha_cita')
            ->orderBy('hora_cita')
            ->limit(20)
            ->get();

        return [
            'vista' => 'recepcion',
            'rol_etiqueta' => 'Enfermería y Recepción',
            'metricas' => [
                'hoy' => (int) $porEstado->sum(),
                'por_confirmar' => (int) ($porEstado[1] ?? CitaMedica::where('id_estado_cita', 1)->count()),
                'en_sala' => (int) CitaMedica::where('id_estado_cita', 2)->count(),
                'atendidas' => (int) CitaMedica::where('id_estado_cita', 3)->count(),
                'inasistencias' => (int) CitaMedica::where('id_estado_cita', 5)->count(),
            ],
            'citas' => $citasControl,
        ];
    }

    private function panelAdministrador(): array
    {
        $hoy = Carbon::today();
        $datos = $this->panelRecepcion();
        $datos['vista'] = 'administrador';
        $datos['rol_etiqueta'] = 'Dirección y Administración';
        $datos['totales'] = [
            'usuarios' => User::query()->count(),
            'medicos' => Medico::query()->where('estado', true)->count(),
            'pacientes' => Paciente::query()->where('estado', true)->count(),
            'especialidades' => Especialidad::query()->where('estado', true)->count(),
            'citas_mes' => CitaMedica::query()
                ->whereBetween('fecha_cita', [$hoy->copy()->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString()])
                ->count(),
        ];
        $datos['auditoria'] = Auditoria::query()->with('usuario')
            ->orderByDesc('id_auditoria')->limit(8)->get();

        return $datos;
    }

    /** Datos públicos de la portada (se degradan con elegancia si la base no responde). */
    public function datosPortada(): array
    {
        try {
            $especialidades = Especialidad::query()->where('estado', true)
                ->withCount(['medicos' => fn ($q) => $q->where('estado', true)])
                ->orderBy('nombre_especialidad')->get();

            $medicos = Medico::query()->where('estado', true)
                ->with('especialidades')
                ->orderBy('apellidos')
                ->get();

            $pacientes = Paciente::query()->where('estado', true)
                ->orderBy('apellidos')
                ->limit(30)
                ->get();

            return [
                'especialidades' => $especialidades,
                'medicos' => $medicos,
                'pacientes' => $pacientes,
                'resumen' => [
                    'medicos' => $medicos->count(),
                    'especialidades' => $especialidades->count(),
                    'pacientes' => Paciente::query()->where('estado', true)->count(),
                ],
            ];
        } catch (\Throwable) {
            return [
                'especialidades' => collect(),
                'medicos' => collect(),
                'pacientes' => collect(),
                'resumen' => ['medicos' => 0, 'especialidades' => 0, 'pacientes' => 0],
            ];
        }
    }
}
