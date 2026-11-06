<?php

namespace App\Http\Controllers;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Services\PortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct(protected PortalService $portal) {}

    /**
     * Portal clínico interactivo: muestra la consola operativa según el rol del usuario
     * o el perfil activo seleccionado en la barra superior / selector de rol.
     */
    public function __invoke(Request $request): View
    {
        $usuario = $request->user()?->loadMissing(['rol', 'medico', 'paciente']);

        if ($request->filled('rol')) {
            $rolSeleccionado = $this->portal->normalizarRol($request->query('rol'));
            if ($request->hasSession()) {
                $request->session()->put('rol_activo', $rolSeleccionado);
            }
        } else {
            $rolSeleccionado = $request->hasSession()
                ? $request->session()->get('rol_activo', $usuario?->nombreRol() ?? 'Médico')
                : ($usuario?->nombreRol() ?? 'Médico');
        }

        $panel = $this->portal->datosPara($usuario, $rolSeleccionado);

        try {
            $especialidades = Especialidad::where('estado', true)->orderBy('nombre_especialidad')->get();
            $medicos = Medico::where('estado', true)->with('especialidades')->orderBy('apellidos')->get();
            $pacientes = Paciente::where('estado', true)->orderBy('apellidos')->limit(40)->get();
        } catch (\Throwable) {
            $especialidades = collect();
            $medicos = collect();
            $pacientes = collect();
        }

        return view('portal.index', [
            'usuario' => $usuario,
            'rolActivo' => $rolSeleccionado,
            'panel' => $panel,
            'especialidades' => $especialidades,
            'medicos' => $medicos,
            'pacientes' => $pacientes,
        ]);
    }
}
