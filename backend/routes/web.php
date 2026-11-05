<?php

use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU03_GestionarUsuariosRoles\UsuariosController;
use App\Http\Controllers\Paquetes\P3_GestionPacientes\CU08_GestionarPerfilPaciente\PerfilPacienteController;
use App\Http\Controllers\Paquetes\P4_GestionCitas\CU11_ProgramarCitaMedica\CitaMedicaController;
use App\Http\Controllers\Paquetes\P4_GestionCitas\CU12_ReprogramarCitaMedica\ReprogramarCitaController;
use App\Http\Controllers\Paquetes\P4_GestionCitas\CU13_CancelarCitaMedica\CancelarCitaController;
use App\Http\Controllers\Paquetes\P5_ControlAtencion\CU14_RegistrarConfirmacionAsistenciaInasistencia\ControlAsistenciaController;
use App\Http\Controllers\Paquetes\P5_ControlAtencion\CU15_RegistrarCitaAtendida\CitaAtendidaController;
use App\Http\Controllers\Paquetes\P6_ConsultasHistoriales\CU16_ConsultarAgendaMedica\AgendaMedicaController;
use App\Http\Controllers\Paquetes\P6_ConsultasHistoriales\CU17_ConsultarHistorialCitasPaciente\HistorialCitasPacienteController;
use App\Http\Controllers\Paquetes\P6_ConsultasHistoriales\CU18_ConsultarMisCitas\MisCitasController;
use App\Http\Controllers\Paquetes\P7_ReportesEstadisticas\CU19_GenerarReportesAdministrativos\ReportesAdminController;
use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Arquitectura 100% PHP Laravel Blade
|--------------------------------------------------------------------------
*/

// Página de bienvenida / institucional
Route::view('/', 'welcome')->name('inicio');

// CU03: Gestión de Usuarios y Roles
Route::get('/usuarios', function () {
    $usuarios = User::with('rol')->get();
    return view('Paquetes.P1_SeguridadAdministracion.CU03_GestionarUsuariosRoles.index', compact('usuarios'));
});

// CU08: Gestionar Perfil del Paciente (Auto-gestión)
Route::get('/perfil-paciente', function (Request $request) {
    $idPaciente = $request->query('id_paciente', 1);
    $paciente = Paciente::find($idPaciente);
    return view('Paquetes.P3_GestionPacientes.CU08_GestionarPerfilPaciente.index', compact('paciente'));
});
Route::put('/perfil-paciente', [PerfilPacienteController::class, 'update']);

// CU11: Programación y Reserva de Citas Médicas
Route::get('/citas', function () {
    $pacientes = Paciente::where('estado', true)->get();
    $especialidades = Especialidad::where('estado', true)->get();
    $medicos = Medico::where('estado', true)->get();
    $citas = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->orderBy('fecha_cita', 'desc')
        ->orderBy('hora_cita', 'desc')
        ->take(15)
        ->get();
    return view('Paquetes.P4_GestionCitas.CU11_ProgramarCitaMedica.index', compact('pacientes', 'especialidades', 'medicos', 'citas'));
});
Route::post('/citas', [CitaMedicaController::class, 'store']);

// CU12: Reprogramar Cita Médica
Route::get('/citas/{id}/reprogramar', function (int $id) {
    $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($id);
    return view('Paquetes.P4_GestionCitas.CU12_ReprogramarCitaMedica.index', compact('cita'));
});
Route::patch('/citas/{id}/reprogramar', ReprogramarCitaController::class);

// CU13: Cancelar Cita Médica
Route::get('/citas/{id}/cancelar', function (int $id) {
    $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($id);
    return view('Paquetes.P4_GestionCitas.CU13_CancelarCitaMedica.index', compact('cita'));
});
Route::patch('/citas/{id}/cancelar', CancelarCitaController::class);

// CU14: Registrar Confirmación, Asistencia e Inasistencia
Route::get('/control-atencion', function () {
    $citas = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->whereIn('id_estado_cita', [1, 2])
        ->orderBy('fecha_cita', 'asc')
        ->orderBy('hora_cita', 'asc')
        ->get();
    return view('Paquetes.P5_ControlAtencion.CU14_RegistrarConfirmacionAsistenciaInasistencia.index', compact('citas'));
});
Route::patch('/control-atencion/citas/{id}/confirmar', [ControlAsistenciaController::class, 'confirmar']);
Route::patch('/control-atencion/citas/{id}/llegada', [ControlAsistenciaController::class, 'registrarLlegada']);
Route::patch('/control-atencion/citas/{id}/inasistencia', [ControlAsistenciaController::class, 'marcarInasistencia']);

// CU15: Registrar Cita Atendida (Médico)
Route::get('/atencion-medica', function (Request $request) {
    $idMedico = $request->query('id_medico', 1);
    $citas = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->where('id_medico', $idMedico)
        ->orderBy('fecha_cita', 'desc')
        ->orderBy('hora_cita', 'asc')
        ->get();
    return view('Paquetes.P5_ControlAtencion.CU15_RegistrarCitaAtendida.index', compact('citas'));
});
Route::patch('/citas/{id}/atender', CitaAtendidaController::class);

// CU16: Consultar Agenda Médica
Route::get('/agenda-medica', function (Request $request) {
    $medicos = Medico::where('estado', true)->get();
    $especialidades = Especialidad::where('estado', true)->get();
    
    $query = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->orderBy('fecha_cita', 'asc')
        ->orderBy('hora_cita', 'asc');

    if ($request->filled('fecha_cita')) {
        $query->whereDate('fecha_cita', $request->fecha_cita);
    }
    if ($request->filled('id_medico')) {
        $query->where('id_medico', $request->id_medico);
    }
    if ($request->filled('id_especialidad')) {
        $query->where('id_especialidad', $request->id_especialidad);
    }
    if ($request->filled('id_estado_cita')) {
        $query->where('id_estado_cita', $request->id_estado_cita);
    }

    $citas = $query->get();
    return view('Paquetes.P6_ConsultasHistoriales.CU16_ConsultarAgendaMedica.index', compact('citas', 'medicos', 'especialidades'));
});

// CU17: Consultar Historial de Citas por Paciente
Route::get('/historial-paciente', function (Request $request) {
    $identificador = $request->query('identificador');
    $paciente = null;
    $citas = collect();

    if ($identificador) {
        $paciente = Paciente::where('ci', $identificador)->orWhere('id_paciente', is_numeric($identificador) ? (int)$identificador : 0)->first();
        if ($paciente) {
            $citas = CitaMedica::with(['medico', 'especialidad', 'estado'])
                ->where('id_paciente', $paciente->id_paciente)
                ->orderBy('fecha_cita', 'desc')
                ->get();
        }
    }

    return view('Paquetes.P6_ConsultasHistoriales.CU17_ConsultarHistorialCitasPaciente.index', compact('paciente', 'citas'));
});

// CU18: Consultar Mis Citas (Portal Paciente)
Route::get('/mis-citas', function (Request $request) {
    $idPaciente = $request->query('id_paciente', 1);
    $paciente = Paciente::find($idPaciente);

    $proximas = CitaMedica::with(['medico', 'especialidad', 'estado'])
        ->where('id_paciente', $idPaciente)
        ->whereIn('id_estado_cita', [1, 2])
        ->orderBy('fecha_cita', 'asc')
        ->get();

    $historial = CitaMedica::with(['medico', 'especialidad', 'estado'])
        ->where('id_paciente', $idPaciente)
        ->whereIn('id_estado_cita', [3, 4, 5])
        ->orderBy('fecha_cita', 'desc')
        ->get();

    return view('Paquetes.P6_ConsultasHistoriales.CU18_ConsultarMisCitas.index', compact('paciente', 'proximas', 'historial'));
});

// CU19: Generar Reportes Administrativos
Route::get('/reportes-administrativos', function (Request $request) {
    $service = app(\App\Services\Paquetes\P7_ReportesEstadisticas\ReportesAdminService::class);
    $reporte = $service->generarReporte($request->all());
    return view('Paquetes.P7_ReportesEstadisticas.CU19_GenerarReportesAdministrativos.index', compact('reporte'));
});
