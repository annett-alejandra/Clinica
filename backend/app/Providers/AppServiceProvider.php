<?php

namespace App\Providers;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Datos compartidos por el layout: usuario, rol activo, módulos CU y catálogos para el modal de reserva.
        View::composer('layouts.app', function ($vista): void {
            try {
                $usuario = Auth::user()?->loadMissing(['rol', 'medico', 'paciente']);
            } catch (\Throwable) {
                $usuario = null;
            }

            $rolSesion = request()->hasSession() ? request()->session()->get('rol_activo') : null;
            $rol = $rolSesion ?: ($usuario?->nombreRol() ?: 'Médico');

            $modulos = collect(config('clinica.modulos'))
                ->filter(fn (array $m) => $m['roles'] === [] || in_array($rol, $m['roles'], true) || $rol === 'Administrador')
                ->groupBy('grupo');

            try {
                $especialidades = Especialidad::query()->where('estado', true)
                    ->orderBy('nombre_especialidad')->get(['id_especialidad', 'nombre_especialidad']);
                $medicos = Medico::query()->where('estado', true)
                    ->orderBy('apellidos')->get(['id_medico', 'nombres', 'apellidos', 'consultorio']);
                $pacientes = Paciente::query()->where('estado', true)
                    ->orderBy('apellidos')->limit(40)->get(['id_paciente', 'nombres', 'apellidos', 'ci']);
            } catch (\Throwable) {
                $especialidades = collect();
                $medicos = collect();
                $pacientes = collect();
            }

            $vista->with([
                'usuarioActual' => $usuario,
                'rolActual' => $rol,
                'modulosMenu' => $modulos,
                'especialidadesReserva' => $especialidades,
                'medicosReserva' => $medicos,
                'pacientesReserva' => $pacientes,
            ]);
        });
    }
}
