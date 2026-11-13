<?php

use App\Http\Controllers\InicioController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU01_IniciarSesion\LoginWebController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU01_IniciarSesion\RecuperarPasswordController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU02_CerrarSesion\LogoutWebController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU03_GestionarUsuariosRoles\UsuariosController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU04_GestionarEspecialidadesMedicas\EspecialidadController;
use App\Http\Controllers\Paquetes\P2_PersonalMedico\CU05_GestionarMedicos\MedicosController;
use App\Http\Controllers\Paquetes\P2_PersonalMedico\CU06_GestionarHorariosAtencion\HorariosController;
use App\Http\Controllers\Paquetes\P3_GestionPacientes\CU07_GestionarPacientes\PacientesController;
use App\Http\Controllers\Paquetes\P3_GestionPacientes\CU08_GestionarPerfilPaciente\PerfilPacienteController;
use App\Http\Controllers\Paquetes\P3_GestionPacientes\CU09_RegistrarCuentaPaciente\RegistroPacienteController;
use App\Http\Controllers\Paquetes\P4_GestionCitas\CU10_ConsultarDisponibilidadMedica\DisponibilidadController;
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
use App\Models\Paquetes\P2_PersonalMedico\HorarioAtencion;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use App\Services\Paquetes\P3_GestionPacientes\PacienteService;
use App\Services\PortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Arquitectura 100% PHP Laravel Blade con Vistas y Controladores
|--------------------------------------------------------------------------
*/

// Inicio Institucional
Route::get('/', InicioController::class)->name('inicio');

// Portal clínico interactivo por rol (requiere autenticación obligatoria)
Route::get('/portal', PortalController::class)->middleware('auth')->name('portal');

// Cambio rápido de perfil / rol en el portal o barra superior
Route::post('/cambiar-rol', function (Request $request, PortalService $portal) {
    $rol = $portal->normalizarRol($request->input('rol'));
    $request->session()->put('rol_activo', $rol);

    return redirect()->route('portal', ['rol' => match ($rol) {
        'Médico' => 'medico',
        'Recepcionista' => 'enfermeria',
        'Paciente' => 'paciente',
        default => 'administrador',
    }])->with('status', "Perfil operativo activo: {$rol}");
})->name('cambiar-rol');

// CU01 & CU02: Iniciar y Cerrar Sesión
Route::get('/login', function () {
    return auth()->check()
        ? redirect()->route('portal')
        : view('Paquetes.P1_SeguridadAdministracion.CU01_IniciarSesion.index');
})->name('login');
Route::post('/login', LoginWebController::class)->middleware('throttle:10,1');
Route::get('/logout', fn() => view('Paquetes.P1_SeguridadAdministracion.CU02_CerrarSesion.index'))->name('logout');
Route::post('/logout', LogoutWebController::class);

// Recuperación de Contraseña con Validación de Políticas
Route::get('/recuperar-password', [RecuperarPasswordController::class, 'index'])->name('recuperar.password');
Route::post('/recuperar-password/solicitar', [RecuperarPasswordController::class, 'solicitar'])->name('recuperar.solicitar');
Route::post('/recuperar-password/confirmar', [RecuperarPasswordController::class, 'confirmar'])->name('recuperar.confirmar');
Route::get('/recuperar-password/reiniciar', [RecuperarPasswordController::class, 'reiniciar'])->name('recuperar.reiniciar');

// CU03: Gestión de Usuarios y Roles (Admin)
Route::get('/usuarios', function () {
    $usuarios = User::with('rol')->get();
    return view('Paquetes.P1_SeguridadAdministracion.CU03_GestionarUsuariosRoles.index', compact('usuarios'));
})->middleware('rol:Administrador');
Route::post('/usuarios', [UsuariosController::class, 'store'])->middleware('rol:Administrador');

// CU04: Especialidades Médicas (Admin)
Route::get('/especialidades', function () {
    $especialidades = Especialidad::all();
    return view('Paquetes.P1_SeguridadAdministracion.CU04_GestionarEspecialidadesMedicas.index', compact('especialidades'));
})->middleware('rol:Administrador');
Route::post('/especialidades', [EspecialidadController::class, 'store'])->middleware('rol:Administrador');

// CU05: Gestión de Personal Médico (Admin, Recepción)
Route::get('/medicos', function () {
    $medicos = Medico::with('especialidades')->get();
    $especialidades = Especialidad::where('estado', true)->get();
    return view('Paquetes.P2_PersonalMedico.CU05_GestionarMedicos.index', compact('medicos', 'especialidades'));
})->middleware('rol:Administrador,Recepcionista');
Route::post('/medicos', [MedicosController::class, 'store'])->middleware('rol:Administrador,Recepcionista');

// CU06: Horarios de Atención (Admin, Recepción, Médico)
Route::get('/horarios', function () {
    $horarios = HorarioAtencion::with('medico')->orderBy('dia_semana')->get();
    $medicos = Medico::where('estado', true)->get();
    return view('Paquetes.P2_PersonalMedico.CU06_GestionarHorariosAtencion.index', compact('horarios', 'medicos'));
})->middleware('rol:Administrador,Recepcionista,Médico');
Route::post('/horarios', [HorariosController::class, 'store'])->middleware('rol:Administrador,Recepcionista,Médico');
Route::delete('/horarios/{id}', [HorariosController::class, 'destroy'])->middleware('rol:Administrador,Recepcionista,Médico');

// CU07: Directorio de Pacientes (Admin, Recepción)
Route::get('/pacientes', function (Request $request, PacienteService $pacienteService) {
    $termino = $request->query('q');
    $soloActivos = $request->query('estado') === 'activos';
    $pacientes = $pacienteService->listar($termino, $soloActivos);
    return view('Paquetes.P3_GestionPacientes.CU07_GestionarPacientes.index', compact('pacientes', 'termino'));
})->middleware('rol:Administrador,Recepcionista');
Route::post('/pacientes', [PacientesController::class, 'store'])->middleware('rol:Administrador,Recepcionista');

// CU08: Gestionar Perfil del Paciente (Paciente)
Route::get('/perfil-paciente', function (Request $request) {
    $usuario = $request->user();
    $idPaciente = $usuario?->tieneRol('Paciente')
        ? (int) $usuario->id_paciente
        : (int) $request->query('id_paciente', 1);
    $paciente = Paciente::find($idPaciente) ?? Paciente::where('estado', true)->first();
    return view('Paquetes.P3_GestionPacientes.CU08_GestionarPerfilPaciente.index', compact('paciente'));
})->middleware('rol:Paciente,Administrador');
Route::put('/perfil-paciente', [PerfilPacienteController::class, 'update'])->middleware('rol:Paciente,Administrador');

// CU09: Auto-registro Público de Pacientes
Route::get('/registro-paciente', fn() => view('Paquetes.P3_GestionPacientes.CU09_RegistrarCuentaPaciente.index'));
Route::post('/registro-paciente', [RegistroPacienteController::class, 'register']);

// CU10: Consultar Disponibilidad Médica (Público / Todos)
Route::get('/disponibilidad', function (Request $request) {
    $especialidades = Especialidad::where('estado', true)->get();
    $medicos = Medico::where('estado', true)->get();
    $turnos = [];

    if ($request->filled('id_medico') && $request->filled('fecha')) {
        $service = app(\App\Services\Paquetes\P4_GestionCitas\DisponibilidadService::class);
        $idMedico = (int) $request->id_medico;
        $idEspecialidad = $request->filled('id_especialidad')
            ? (int) $request->id_especialidad
            : (int) Medico::find($idMedico)?->especialidades()->value('especialidades.id_especialidad');

        try {
            $oferta = $service->consultar($idEspecialidad, $idMedico, $request->fecha);
            $turnos = collect($oferta)->flatMap(fn (array $o) => $o['cupos'])->values()->all();
        } catch (\Throwable $e) {
            session()->now('error', $e instanceof \InvalidArgumentException
                ? $e->getMessage()
                : 'No fue posible consultar la disponibilidad. Verifica la especialidad, el médico y la fecha.');
        }
    }

    return view('Paquetes.P4_GestionCitas.CU10_ConsultarDisponibilidadMedica.index', compact('especialidades', 'medicos', 'turnos'));
});

// CU11: Programar Cita Médica (Admin, Recepción, Paciente)
Route::get('/citas', function (Request $request) {
    $usuario = $request->user();
    $esPaciente = $usuario?->tieneRol('Paciente') ?? false;
    $idPaciente = (int) ($usuario?->id_paciente ?? 0);

    $pacientes = Paciente::where('estado', true)
        ->when($esPaciente && $idPaciente > 0, fn ($q) => $q->where('id_paciente', $idPaciente))
        ->orderBy('id_paciente', 'desc')
        ->get();

    $especialidades = Especialidad::where('estado', true)
        ->orderBy('nombre_especialidad')
        ->get();

    $medicos = Medico::where('estado', true)
        ->with(['especialidades', 'horariosAtencion'])
        ->orderBy('nombre_completo')
        ->get();

    $citas = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->when($esPaciente && $idPaciente > 0, fn ($q) => $q->where('id_paciente', $idPaciente))
        ->orderBy('fecha_cita', 'desc')
        ->orderBy('hora_cita', 'desc')
        ->take(20)
        ->get();

    return view('Paquetes.P4_GestionCitas.CU11_ProgramarCitaMedica.index', compact('pacientes', 'especialidades', 'medicos', 'citas'));
})->middleware('rol:Administrador,Recepcionista,Paciente');
Route::post('/citas', [CitaMedicaController::class, 'store'])->middleware('rol:Administrador,Recepcionista,Paciente');

// CU12: Reprogramar Cita Médica (Recepción, Admin)
Route::get('/citas/{id}/reprogramar', function (int $id) {
    $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($id);
    return view('Paquetes.P4_GestionCitas.CU12_ReprogramarCitaMedica.index', compact('cita'));
})->middleware('rol:Administrador,Recepcionista');
Route::patch('/citas/{id}/reprogramar', ReprogramarCitaController::class)->middleware('rol:Administrador,Recepcionista');

// CU13: Cancelar Cita Médica (Recepción, Admin)
Route::get('/citas/{id}/cancelar', function (int $id) {
    $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($id);
    return view('Paquetes.P4_GestionCitas.CU13_CancelarCitaMedica.index', compact('cita'));
})->middleware('rol:Administrador,Recepcionista');
Route::patch('/citas/{id}/cancelar', CancelarCitaController::class)->middleware('rol:Administrador,Recepcionista');

// CU14: Registrar Confirmación, Asistencia e Inasistencia (Recepción, Admin)
Route::get('/control-atencion', function () {
    $citas = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->whereIn('id_estado_cita', [1, 2])
        ->orderBy('fecha_cita', 'asc')
        ->orderBy('hora_cita', 'asc')
        ->get();
    return view('Paquetes.P5_ControlAtencion.CU14_RegistrarConfirmacionAsistenciaInasistencia.index', compact('citas'));
})->middleware('rol:Administrador,Recepcionista');
Route::patch('/control-atencion/citas/{id}/confirmar', [ControlAsistenciaController::class, 'confirmar'])->middleware('rol:Administrador,Recepcionista');
Route::patch('/control-atencion/citas/{id}/llegada', [ControlAsistenciaController::class, 'registrarLlegada'])->middleware('rol:Administrador,Recepcionista');
Route::patch('/control-atencion/citas/{id}/inasistencia', [ControlAsistenciaController::class, 'marcarInasistencia'])->middleware('rol:Administrador,Recepcionista');

// CU15: Registrar Cita Atendida (Médico)
Route::get('/atencion-medica', function (Request $request) {
    $usuario = $request->user();
    $idMedico = $usuario?->tieneRol('Médico') && $usuario->id_medico
        ? (int) $usuario->id_medico
        : (int) ($request->query('id_medico') ?: Medico::where('estado', true)->value('id_medico') ?: 1);
    $citas = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->where('id_medico', $idMedico)
        ->orderBy('fecha_cita', 'desc')
        ->orderBy('hora_cita', 'asc')
        ->get();
    return view('Paquetes.P5_ControlAtencion.CU15_RegistrarCitaAtendida.index', compact('citas'));
})->middleware('rol:Médico,Administrador');
Route::patch('/citas/{id}/atender', CitaAtendidaController::class)->middleware('rol:Médico,Administrador');

// CU16: Consultar Agenda Médica (Admin, Recepción, Médico)
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
})->middleware('rol:Administrador,Recepcionista,Médico');

// CU17: Consultar Historial de Citas por Paciente (Admin, Recepción)
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
})->middleware('rol:Administrador,Recepcionista');

// CU18: Consultar Mis Citas (Paciente)
Route::get('/mis-citas', function (Request $request) {
    $usuario = $request->user();
    $idPaciente = $usuario?->tieneRol('Paciente') && $usuario->id_paciente
        ? (int) $usuario->id_paciente
        : (int) ($request->query('id_paciente') ?: Paciente::where('estado', true)->value('id_paciente') ?: 1);
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
})->middleware('rol:Paciente,Administrador');

// CU19: Generar Reportes Administrativos (Admin)
Route::get('/reportes-administrativos', function (Request $request) {
    $service = app(\App\Services\Paquetes\P7_ReportesEstadisticas\ReportesAdminService::class);
    $reporte = $service->generarReporte($request->all());
    return view('Paquetes.P7_ReportesEstadisticas.CU19_GenerarReportesAdministrativos.index', compact('reporte'));
})->middleware('rol:Administrador');
