<?php

use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU01_IniciarSesion\AuthController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU02_CerrarSesion\LogoutController;
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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Arquitectura de Casos de Uso (CU01 a CU19)
|--------------------------------------------------------------------------
*/

// ==========================================
// PAQUETE 1: SEGURIDAD Y ADMINISTRACIÓN
// ==========================================
// CU01 & CU02: Iniciar y Cerrar Sesión
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', LogoutController::class);
});

// CU03: Gestión de Usuarios y Roles
Route::prefix('usuarios')->group(function () {
    Route::get('/', [UsuariosController::class, 'index']);
    Route::get('/roles', [UsuariosController::class, 'roles']);
    Route::post('/', [UsuariosController::class, 'store']);
    Route::put('/{id}', [UsuariosController::class, 'update']);
    Route::patch('/{id}/toggle-estado', [UsuariosController::class, 'toggleEstado']);
});

// CU04: Catálogo de Especialidades Médicas
Route::prefix('especialidades')->group(function () {
    Route::get('/', [EspecialidadController::class, 'index']);
    Route::post('/', [EspecialidadController::class, 'store']);
    Route::put('/{id}', [EspecialidadController::class, 'update']);
    Route::patch('/{id}/toggle-estado', [EspecialidadController::class, 'toggleEstado']);
});

// ==========================================
// PAQUETE 2: PERSONAL MÉDICO Y HORARIOS
// ==========================================
// CU05: Gestión de Personal Médico
Route::prefix('medicos')->group(function () {
    Route::get('/', [MedicosController::class, 'index']);
    Route::post('/', [MedicosController::class, 'store']);
    Route::put('/{id}', [MedicosController::class, 'update']);
    Route::patch('/{id}/toggle-estado', [MedicosController::class, 'toggleEstado']);
});

// CU06: Horarios y Turnos de Atención Médica
Route::prefix('horarios')->group(function () {
    Route::get('/', [HorariosController::class, 'index']);
    Route::post('/', [HorariosController::class, 'store']);
    Route::delete('/{id}', [HorariosController::class, 'destroy']);
});

// ==========================================
// PAQUETE 3: GESTIÓN DE PACIENTES
// ==========================================
// CU07: Administración de Fichas de Pacientes
Route::prefix('pacientes')->group(function () {
    Route::get('/', [PacientesController::class, 'index']);
    Route::post('/', [PacientesController::class, 'store']);
    Route::put('/{id}', [PacientesController::class, 'update']);
    Route::patch('/{id}/toggle-estado', [PacientesController::class, 'toggleEstado']);
});

// CU08: Gestionar Perfil del Paciente (Auto-gestión)
Route::prefix('perfil-paciente')->group(function () {
    Route::get('/', [PerfilPacienteController::class, 'show']);
    Route::put('/', [PerfilPacienteController::class, 'update']);
});

// CU09: Auto-registro Público de Pacientes
Route::post('/registro-paciente', [RegistroPacienteController::class, 'register']);

// ==========================================
// PAQUETE 4: GESTIÓN DE CITAS MÉDICAS
// ==========================================
// CU10: Consulta Dinámica de Disponibilidad Médica
Route::get('/disponibilidad', [DisponibilidadController::class, 'consultar']);

// CU11: Programación y Reserva de Citas Médicas
Route::prefix('citas')->group(function () {
    Route::get('/', [CitaMedicaController::class, 'index']);
    Route::get('/{codigo}', [CitaMedicaController::class, 'show']);
    Route::post('/', [CitaMedicaController::class, 'store']);

    // CU12: Reprogramar Cita Médica
    Route::patch('/{id}/reprogramar', ReprogramarCitaController::class);

    // CU13: Cancelar Cita Médica
    Route::patch('/{id}/cancelar', CancelarCitaController::class);
});

// ==========================================
// PAQUETE 5: CONTROL DE ATENCIÓN OPERATIVA
// ==========================================
// CU14: Registrar Confirmación, Asistencia e Inasistencia
Route::prefix('control-atencion')->group(function () {
    Route::patch('/citas/{id}/confirmar', [ControlAsistenciaController::class, 'confirmar']);
    Route::patch('/citas/{id}/llegada', [ControlAsistenciaController::class, 'registrarLlegada']);
    Route::patch('/citas/{id}/inasistencia', [ControlAsistenciaController::class, 'marcarInasistencia']);
});

// CU15: Registrar Cita Médica Atendida
Route::patch('/citas/{id}/atender', CitaAtendidaController::class);

// ==========================================
// PAQUETE 6: CONSULTAS E HISTORIALES
// ==========================================
// CU16: Consultar Agenda Médica Dinámica
Route::get('/agenda-medica', AgendaMedicaController::class);

// CU17: Consultar Historial de Citas por Paciente
Route::get('/historial-paciente/{identificador}', HistorialCitasPacienteController::class);

// CU18: Consultar Mis Citas (Portal Paciente)
Route::get('/mis-citas', MisCitasController::class);

// ==========================================
// PAQUETE 7: REPORTES Y ESTADÍSTICAS
// ==========================================
// CU19: Generar Reportes Administrativos
Route::get('/reportes-administrativos', ReportesAdminController::class);
