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
Route::get('/usuarios', function (Request $request) {
    $busqueda = trim($request->query('q', ''));
    $rolFiltro = $request->query('rol', 'todos');

    $query = User::with('rol')->orderBy('id_usuario', 'desc');

    if ($busqueda !== '') {
        $query->where(function ($q) use ($busqueda) {
            $q->where('nombre_usuario', 'ilike', "%{$busqueda}%")
              ->orWhere('correo', 'ilike', "%{$busqueda}%");
        });
    }

    if ($rolFiltro !== 'todos' && $rolFiltro !== '') {
        $query->whereHas('rol', fn ($q) => $q->where('nombre_rol', $rolFiltro));
    }

    $usuarios = $query->paginate(15)->withQueryString();

    $conteos = [
        'total' => User::count(),
        'admin' => User::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'Administrador'))->count(),
        'medico' => User::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'Médico'))->count(),
        'recepcion' => User::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'Recepcionista'))->count(),
        'paciente' => User::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'Paciente'))->count(),
    ];

    return view('Paquetes.P1_SeguridadAdministracion.CU03_GestionarUsuariosRoles.index', compact('usuarios', 'conteos', 'busqueda', 'rolFiltro'));
})->middleware('rol:Administrador');
Route::post('/usuarios', [UsuariosController::class, 'store'])->middleware('rol:Administrador');

// Módulo de Auditoría y Seguridad del Sistema (Exclusivo Administrador)
// CU03 - AUDITORÍA Y SEGURIDAD: Exclusivo Administrador del Sistema
Route::get('/auditoria', function (Request $request) {
    $accion = $request->query('accion');
    $tabla = $request->query('tabla');
    $categoria = $request->query('categoria', 'todas');
    $idUsuario = $request->query('id_usuario');
    $busqueda = trim($request->query('q', ''));
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    // Métricas globales de seguridad para el Administrador
    $kpis = [
        'total' => \App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria::count(),
        'logins' => \App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria::where('accion', 'LOGIN')->count(),
        'logouts' => \App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria::where('accion', 'LOGOUT')->count(),
        'fallidos' => \App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria::whereIn('accion', ['INTENTO_LOGIN_FALLIDO', 'ACCESO_DENEGADO'])->count(),
        'clinicos' => \App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria::where(function ($q) {
            $q->where('tabla_afectada', 'like', '%cita%')
              ->orWhere('tabla_afectada', 'like', '%paciente%')
              ->orWhere('accion', 'like', '%CITA%')
              ->orWhere('accion', 'like', '%PACIENTE%')
              ->orWhere('accion', 'like', '%ATENDIDA%');
        })->count(),
    ];

    $query = \App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria::with('usuario.rol')
        ->orderBy('id_auditoria', 'desc');

    // Filtro por categorías rápidas (Quién entra, Quién sale, Alertas, etc.)
    if ($categoria === 'entradas') {
        $query->where('accion', 'LOGIN');
    } elseif ($categoria === 'salidas') {
        $query->where('accion', 'LOGOUT');
    } elseif ($categoria === 'fallidos') {
        $query->whereIn('accion', ['INTENTO_LOGIN_FALLIDO', 'ACCESO_DENEGADO']);
    } elseif ($categoria === 'accesos') {
        $query->whereIn('accion', [
            'LOGIN', 'LOGOUT', 'INTENTO_LOGIN_FALLIDO', 'ACCESO_DENEGADO',
            'SOLICITUD_RECUPERACION_PASSWORD', 'RESTABLECIMIENTO_PASSWORD_EXITOSO'
        ]);
    } elseif ($categoria === 'clinica') {
        $query->where(function ($q) {
            $q->where('tabla_afectada', 'like', '%cita%')
              ->orWhere('tabla_afectada', 'like', '%paciente%')
              ->orWhere('accion', 'like', '%CITA%')
              ->orWhere('accion', 'like', '%PACIENTE%')
              ->orWhere('accion', 'like', '%ATENDIDA%')
              ->orWhere('accion', 'like', '%LLEGADA%')
              ->orWhere('accion', 'like', '%EXPEDIENTE%')
              ->orWhere('accion', 'like', '%INASISTENCIA%');
        });
    } elseif ($categoria === 'admin') {
        $query->where(function ($q) {
            $q->where('tabla_afectada', 'like', '%horario%')
              ->orWhere('tabla_afectada', 'like', '%usuario%')
              ->orWhere('tabla_afectada', 'like', '%especialidad%')
              ->orWhere('tabla_afectada', 'like', '%medico%')
              ->orWhere('tabla_afectada', 'seguridad_rutas')
              ->orWhere('accion', 'like', '%PASSWORD%')
              ->orWhere('accion', 'like', '%USUARIO%')
              ->orWhere('accion', 'like', '%HORARIO%');
        });
    }

    // Filtros por combos
    if ($accion && $accion !== 'todas') {
        $query->where('accion', $accion);
    }
    if ($tabla && $tabla !== 'todas') {
        $query->where('tabla_afectada', $tabla);
    }
    if ($idUsuario && $idUsuario !== 'todos') {
        $query->where('id_usuario', (int) $idUsuario);
    }

    // Filtro por rango de fechas en hora local de Bolivia (America/La_Paz)
    if ($desde) {
        $query->whereRaw("(fecha_hora AT TIME ZONE 'America/La_Paz')::date >= ?", [$desde]);
    }
    if ($hasta) {
        $query->whereRaw("(fecha_hora AT TIME ZONE 'America/La_Paz')::date <= ?", [$hasta]);
    }

    // Búsqueda general (Operador, correo, acción, tabla, IP, CI paciente, motivo)
    if ($busqueda !== '') {
        $query->where(function ($q) use ($busqueda) {
            $q->where('accion', 'ilike', "%{$busqueda}%")
              ->orWhere('tabla_afectada', 'ilike', "%{$busqueda}%")
              ->orWhereRaw("detalle::text ilike ?", ["%{$busqueda}%"])
              ->orWhereHas('usuario', fn($u) => $u->where('nombre_usuario', 'ilike', "%{$busqueda}%")->orWhere('correo', 'ilike', "%{$busqueda}%"));
        });
    }

    // Exportar CSV de Auditoría si se solicita ?formato=csv
    if ($request->query('formato') === 'csv') {
        $rows = $query->limit(1000)->get();
        $filename = 'bitacora_auditoria_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ID', 'Fecha/Hora (Bolivia)', 'Operador', 'Rol', 'Accion', 'Tabla', 'ID Registro', 'IP', 'Estado Anterior', 'Estado Nuevo', 'Paciente CI', 'Justificacion']);
            foreach ($rows as $row) {
                $det = $row->detalle ?? [];
                fputcsv($handle, [
                    $row->id_auditoria,
                    \Carbon\Carbon::parse($row->fecha_hora)->timezone('America/La_Paz')->format('d/m/Y H:i:s'),
                    $row->usuario?->nombre_usuario ?? ($det['identificador'] ?? 'Sistema/Anonimo'),
                    $row->usuario?->rol?->nombre_rol ?? ($det['rol'] ?? $det['rol_actual'] ?? '-'),
                    $row->accion,
                    $row->tabla_afectada,
                    $row->id_registro_afectado ?? '-',
                    $det['ip'] ?? '-',
                    $det['estado_anterior'] ?? '-',
                    $det['estado_nuevo'] ?? '-',
                    $det['paciente']['ci'] ?? '-',
                    $det['motivo_justificacion'] ?? '-',
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    $auditorias = $query->paginate(25)->withQueryString();

    $acciones = \App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria::select('accion')
        ->distinct()
        ->orderBy('accion')
        ->pluck('accion');

    $tablas = \App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria::select('tabla_afectada')
        ->distinct()
        ->orderBy('tabla_afectada')
        ->pluck('tabla_afectada');

    $usuariosLista = User::orderBy('nombre_usuario')->get(['id_usuario', 'nombre_usuario', 'correo']);

    return view('Paquetes.P1_SeguridadAdministracion.Auditoria.index', compact(
        'auditorias', 'acciones', 'tablas', 'usuariosLista', 'accion', 'tabla', 'idUsuario', 'busqueda', 'categoria', 'desde', 'hasta', 'kpis'
    ));
})->middleware('rol:Administrador')->name('auditoria');

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
    $especialidades = Especialidad::where('estado', true)->orderBy('nombre_especialidad')->get();
    $medicos = Medico::where('estado', true)->with(['especialidades', 'horariosAtencion'])->orderBy('nombre_completo')->get();
    $turnos = [];
    $ofertaMedica = [];

    if ($request->filled('fecha')) {
        $service = app(\App\Services\Paquetes\P4_GestionCitas\DisponibilidadService::class);
        $idMedico = $request->filled('id_medico') && (int)$request->id_medico > 0 ? (int) $request->id_medico : null;
        $idEspecialidad = $request->filled('id_especialidad')
            ? (int) $request->id_especialidad
            : ($idMedico ? (int) Medico::find($idMedico)?->especialidades()->value('especialidades.id_especialidad') : 1);

        try {
            $ofertaMedica = $service->consultar($idEspecialidad, $idMedico, $request->fecha);
            $turnos = collect($ofertaMedica)->flatMap(fn (array $o) => $o['cupos'])->values()->all();
        } catch (\Throwable $e) {
            session()->now('error', $e instanceof \InvalidArgumentException
                ? $e->getMessage()
                : 'No fue posible consultar la disponibilidad. Verifica la especialidad, el médico y la fecha.');
        }
    }

    return view('Paquetes.P4_GestionCitas.CU10_ConsultarDisponibilidadMedica.index', compact('especialidades', 'medicos', 'turnos', 'ofertaMedica'));
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

// CU12: Reprogramar Cita Médica (Recepción, Admin, y Paciente para sus propias citas)
Route::get('/citas/{id}/reprogramar', function (Request $request, int $id) {
    $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($id);
    $usuario = $request->user();
    if ($usuario?->tieneRol('Paciente') && (int) $cita->id_paciente !== (int) $usuario->id_paciente) {
        \App\Services\Paquetes\P1_SeguridadAdministracion\AuditService::registrar(
            $usuario->id_usuario,
            'ACCESO_DENEGADO',
            'citas_medicas',
            $id,
            ['accion_ejecutada' => 'ACCESO_DENEGADO', 'motivo_justificacion' => 'Paciente intentó abrir reprogramación de cita ajena']
        );
        return redirect('/mis-citas')->with('error', 'Acceso denegado: solo puedes reprogramar tus propias citas.');
    }
    return view('Paquetes.P4_GestionCitas.CU12_ReprogramarCitaMedica.index', compact('cita'));
})->middleware('rol:Administrador,Recepcionista,Paciente');
Route::patch('/citas/{id}/reprogramar', ReprogramarCitaController::class)->middleware('rol:Administrador,Recepcionista,Paciente');

// CU13: Cancelar Cita Médica (Recepción, Admin, y Paciente para sus propias citas)
Route::get('/citas/{id}/cancelar', function (Request $request, int $id) {
    $cita = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])->findOrFail($id);
    $usuario = $request->user();
    if ($usuario?->tieneRol('Paciente') && (int) $cita->id_paciente !== (int) $usuario->id_paciente) {
        \App\Services\Paquetes\P1_SeguridadAdministracion\AuditService::registrar(
            $usuario->id_usuario,
            'ACCESO_DENEGADO',
            'citas_medicas',
            $id,
            ['accion_ejecutada' => 'ACCESO_DENEGADO', 'motivo_justificacion' => 'Paciente intentó abrir cancelación de cita ajena']
        );
        return redirect('/mis-citas')->with('error', 'Acceso denegado: solo puedes cancelar tus propias citas.');
    }
    return view('Paquetes.P4_GestionCitas.CU13_CancelarCitaMedica.index', compact('cita'));
})->middleware('rol:Administrador,Recepcionista,Paciente');
Route::patch('/citas/{id}/cancelar', CancelarCitaController::class)->middleware('rol:Administrador,Recepcionista,Paciente');

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
    $esMedico = $usuario?->tieneRol('Médico') && $usuario->id_medico;
    $idMedico = $request->filled('id_medico')
        ? (int) $request->id_medico
        : ($esMedico ? (int) $usuario->id_medico : (Medico::where('estado', true)->value('id_medico') ?: 1));

    $medicos = Medico::where('estado', true)->orderBy('nombre_completo')->get();

    $citas = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->where('id_medico', $idMedico)
        ->orderBy('fecha_cita', 'desc')
        ->orderBy('hora_cita', 'asc')
        ->get();
    return view('Paquetes.P5_ControlAtencion.CU15_RegistrarCitaAtendida.index', compact('citas', 'medicos', 'idMedico'));
})->middleware('rol:Médico,Administrador');
Route::patch('/citas/{id}/atender', CitaAtendidaController::class)->middleware('rol:Médico,Administrador');

// CU16: Consultar Agenda Médica (Admin, Recepción, Médico)
Route::get('/agenda-medica', function (Request $request) {
    $usuario = $request->user();
    $esMedico = $usuario?->tieneRol('Médico') && $usuario->id_medico;

    // Aislamiento estricto para Médico; comparación paralela o selección múltiple para Admin/Recepción
    $medicosIdsSeleccionados = [];
    if ($esMedico) {
        $idMedicoSeleccionado = (int) $usuario->id_medico;
        $medicosIdsSeleccionados = [$idMedicoSeleccionado];
    } else {
        $idsInput = $request->input('medicos_ids', []);
        if (is_array($idsInput) && count($idsInput) > 0) {
            $medicosIdsSeleccionados = array_values(array_filter(array_map('intval', $idsInput)));
            $idMedicoSeleccionado = count($medicosIdsSeleccionados) === 1 ? $medicosIdsSeleccionados[0] : null;
        } elseif ($request->filled('id_medico')) {
            $idMedicoSeleccionado = (int) $request->id_medico;
            $medicosIdsSeleccionados = [$idMedicoSeleccionado];
        } else {
            $idMedicoSeleccionado = null;
        }
    }

    $medicos = Medico::where('estado', true)
        ->with(['horariosAtencion', 'especialidades'])
        ->orderBy('nombre_completo')
        ->get();
    $especialidades = Especialidad::where('estado', true)->orderBy('nombre_especialidad')->get();

    // Cálculo de Semana para Vista Google Calendar
    $fechaRef = $request->query('semana') ? \Carbon\Carbon::parse($request->query('semana')) : \Carbon\Carbon::now();
    $inicioSemana = $fechaRef->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $finSemana = $fechaRef->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

    $semanaAnterior = $inicioSemana->copy()->subWeek()->format('Y-m-d');
    $semanaSiguiente = $inicioSemana->copy()->addWeek()->format('Y-m-d');
    $semanaHoy = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d');

    $diasSemana = [];
    for ($i = 0; $i < 7; $i++) {
        $d = $inicioSemana->copy()->addDays($i);
        $diasSemana[] = [
            'fecha' => $d->format('Y-m-d'),
            'dia_nombre' => match ($d->dayOfWeekIso) {
                1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
            },
            'dia_mes' => $d->format('d/m'),
            'dia_iso' => $d->dayOfWeekIso,
            'es_hoy' => $d->isToday(),
        ];
    }

    // Horarios según médico único o múltiples médicos en paralelo
    $modoParalelo = !$esMedico && (count($medicosIdsSeleccionados) !== 1);
    $medicoActual = ($idMedicoSeleccionado)
        ? $medicos->firstWhere('id_medico', $idMedicoSeleccionado)
        : null;

    $medicosActivosFiltro = count($medicosIdsSeleccionados) > 0
        ? $medicos->whereIn('id_medico', $medicosIdsSeleccionados)
        : $medicos;

    $horariosMedico = $medicosActivosFiltro->flatMap(fn($m) => $m->horariosAtencion);

    $query = CitaMedica::with(['paciente', 'medico', 'especialidad', 'estado'])
        ->orderBy('fecha_cita', 'asc')
        ->orderBy('hora_cita', 'asc');

    if (count($medicosIdsSeleccionados) > 0) {
        $query->whereIn('id_medico', $medicosIdsSeleccionados);
    }
    if ($request->filled('fecha_cita')) {
        $query->whereDate('fecha_cita', $request->fecha_cita);
    } else {
        $query->whereBetween('fecha_cita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')]);
    }
    if ($request->filled('id_especialidad')) {
        $query->where('id_especialidad', $request->id_especialidad);
    }
    if ($request->filled('id_estado_cita')) {
        $query->where('id_estado_cita', $request->id_estado_cita);
    }

    $citas = $query->get();

    return view('Paquetes.P6_ConsultasHistoriales.CU16_ConsultarAgendaMedica.index', compact(
        'citas', 'medicos', 'especialidades', 'medicoActual', 'idMedicoSeleccionado',
        'medicosIdsSeleccionados', 'medicosActivosFiltro', 'modoParalelo', 'esMedico',
        'diasSemana', 'inicioSemana', 'finSemana', 'semanaAnterior', 'semanaSiguiente', 'semanaHoy', 'horariosMedico'
    ));
})->middleware('rol:Administrador,Recepcionista,Médico');

// CU17: Consultar Historial de Citas por Paciente (Admin, Recepción, Médico)
Route::get('/historial-paciente', function (Request $request) {
    $busqueda = trim($request->query('identificador', $request->query('q', '')));
    $paciente = null;
    $citas = collect();

    // Lista de pacientes sugeridos para selección rápida
    $pacientesSugeridos = Paciente::where('estado', true)
        ->orderByRaw("CASE WHEN ci = '13730890' THEN 0 ELSE 1 END")
        ->orderBy('id_paciente', 'desc')
        ->take(8)
        ->get();

    if ($busqueda !== '') {
        $paciente = Paciente::where('ci', $busqueda)
            ->orWhere('id_paciente', is_numeric($busqueda) ? (int)$busqueda : 0)
            ->orWhere('correo', 'ilike', "%{$busqueda}%")
            ->orWhere('nombre_completo', 'ilike', "%{$busqueda}%")
            ->first();
    } else {
        // Por defecto cargar a Annett Cruz si no hay búsqueda para mostrar expediente clínico completo
        $paciente = Paciente::where('ci', '13730890')->first() ?? $pacientesSugeridos->first();
    }

    if ($paciente) {
        $citas = CitaMedica::with(['medico', 'especialidad', 'estado'])
            ->where('id_paciente', $paciente->id_paciente)
            ->orderBy('fecha_cita', 'desc')
            ->orderBy('hora_cita', 'desc')
            ->get();
    }

    return view('Paquetes.P6_ConsultasHistoriales.CU17_ConsultarHistorialCitasPaciente.index', compact('paciente', 'citas', 'pacientesSugeridos', 'busqueda'));
})->middleware('rol:Administrador,Recepcionista,Médico');

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
    $especialidades = Especialidad::where('estado', true)->orderBy('nombre_especialidad')->get();
    $medicos = Medico::where('estado', true)->orderBy('nombre_completo')->get();

    // Exportación tabular a formato CSV / Excel si se solicita ?formato=csv
    if ($request->query('formato') === 'csv') {
        $filename = 'reporte_estadistico_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $callback = function () use ($reporte) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fputcsv($handle, ['CLINICA VIDA SALUD - REPORTE GERENCIAL Y ESTADISTICAS CLINICAS']);
            fputcsv($handle, ['Fecha de Emision', date('d/m/Y H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, ['--- METRICAS CLAVE (KPIS) ---']);
            fputcsv($handle, ['Metrica', 'Valor']);
            fputcsv($handle, ['Total de Citas', $reporte['kpis']['total_citas']]);
            fputcsv($handle, ['Citas Atendidas', $reporte['kpis']['atendidas']]);
            fputcsv($handle, ['Tasa de Efectividad (%)', $reporte['kpis']['tasa_efectividad_pct'] . '%']);
            fputcsv($handle, ['Citas Canceladas', $reporte['kpis']['canceladas']]);
            fputcsv($handle, ['Tasa de Cancelacion (%)', $reporte['kpis']['tasa_cancelacion_pct'] . '%']);
            fputcsv($handle, ['Inasistencias', $reporte['kpis']['inasistencias']]);
            fputcsv($handle, ['Tasa de Inasistencia (%)', $reporte['kpis']['tasa_inasistencia_pct'] . '%']);
            fputcsv($handle, []);
            fputcsv($handle, ['--- DISTRIBUCION POR ESTADOS ---']);
            fputcsv($handle, ['Estado', 'Total Citas', 'Porcentaje']);
            foreach ($reporte['distribucion_estados'] as $est) {
                fputcsv($handle, [$est['nombre_estado'], $est['total'], $est['porcentaje'] . '%']);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['--- DEMANDA POR ESPECIALIDAD ---']);
            fputcsv($handle, ['Especialidad', 'Total Citas']);
            foreach ($reporte['por_especialidad'] as $esp) {
                fputcsv($handle, [$esp->nombre_especialidad, $esp->total]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['--- ACTIVIDAD POR MEDICO ---']);
            fputcsv($handle, ['Medico Especialista', 'Total Citas']);
            foreach ($reporte['por_medico'] as $m) {
                fputcsv($handle, [$m->nombre_completo, $m->total]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['--- REPORTE TABULAR DETALLADO DE CITAS ---']);
            fputcsv($handle, ['ID', 'Codigo Unico', 'Fecha', 'Hora', 'Paciente', 'CI', 'Medico', 'Especialidad', 'Estado', 'Motivo']);
            foreach ($reporte['detalle_citas'] as $cita) {
                fputcsv($handle, [
                    $cita->id_cita,
                    $cita->codigo_unico,
                    $cita->fecha_cita,
                    $cita->hora_cita,
                    $cita->nombre_paciente,
                    $cita->ci_paciente,
                    $cita->nombre_medico,
                    $cita->nombre_especialidad,
                    $cita->nombre_estado,
                    $cita->motivo,
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    return view('Paquetes.P7_ReportesEstadisticas.CU19_GenerarReportesAdministrativos.index', compact('reporte', 'especialidades', 'medicos'));
})->middleware('rol:Administrador')->name('reportes-administrativos');
