<?php

use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU01_IniciarSesion\AuthController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU02_CerrarSesion\LogoutController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU03_GestionarUsuariosRoles\UsuariosController;
use App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU04_GestionarEspecialidadesMedicas\EspecialidadController;
use App\Http\Controllers\Paquetes\P2_PersonalMedico\CU05_GestionarMedicos\MedicosController;
use App\Http\Controllers\Paquetes\P2_PersonalMedico\CU06_GestionarHorariosAtencion\HorariosController;
use App\Http\Controllers\Paquetes\P3_GestionPacientes\CU07_GestionarPacientes\PacientesController;
use App\Http\Controllers\Paquetes\P3_GestionPacientes\CU09_RegistrarCuentaPaciente\RegistroPacienteController;
use App\Http\Controllers\Paquetes\P4_GestionCitas\CU10_ConsultarDisponibilidadMedica\DisponibilidadController;
use App\Http\Controllers\Paquetes\P4_GestionCitas\CU11_ProgramarCitaMedica\CitaMedicaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Ciclo 1: Base, Seguridad y Programación
|--------------------------------------------------------------------------
*/

// CU01 & CU02: Autenticación y Control de Sesión
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

// CU07: Administración de Fichas de Pacientes
Route::prefix('pacientes')->group(function () {
    Route::get('/', [PacientesController::class, 'index']);
    Route::post('/', [PacientesController::class, 'store']);
    Route::put('/{id}', [PacientesController::class, 'update']);
    Route::patch('/{id}/toggle-estado', [PacientesController::class, 'toggleEstado']);
});

// CU09: Auto-registro Público de Pacientes
Route::post('/registro-paciente', [RegistroPacienteController::class, 'register']);

// CU10: Consulta Dinámica de Disponibilidad Médica
Route::get('/disponibilidad', [DisponibilidadController::class, 'consultar']);

// CU11: Programación y Reserva de Citas Médicas
Route::prefix('citas')->group(function () {
    Route::get('/', [CitaMedicaController::class, 'index']);
    Route::get('/{codigo}', [CitaMedicaController::class, 'show']);
    Route::post('/', [CitaMedicaController::class, 'store']);
});
