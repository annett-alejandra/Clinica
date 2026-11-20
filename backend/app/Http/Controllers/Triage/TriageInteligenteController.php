<?php

namespace App\Http\Controllers\Triage;

use App\Http\Controllers\Controller;
use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P2_PersonalMedico\PerfilClinicoMedico;
use App\Models\Paquetes\P4_GestionCitas\ConsultaTriage;
use App\Services\Triage\EmbeddingProviderService;
use App\Services\Triage\KnowledgeBaseSyncService;
use App\Services\Triage\TriageInteligenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class TriageInteligenteController extends Controller
{
    public function __construct(
        protected TriageInteligenteService $triageService,
        protected KnowledgeBaseSyncService $knowledgeBase,
        protected EmbeddingProviderService $embeddingProvider
    ) {}

    /**
     * Vista principal del Sistema de Triage Clínico Preliminar asistido por IA.
     */
    public function index(Request $request): View
    {
        $usuario = $request->user()?->loadMissing(['rol', 'paciente']);
        $proveedorActivo = $this->embeddingProvider->proveedorActivo();

        $historialReciente = collect();
        $totalEspecialidades = 0;
        $totalMedicos = 0;

        try {
            $totalEspecialidades = Especialidad::where('estado', true)->count();
            $totalMedicos = Medico::where('estado', true)->count();

            if ($usuario && Schema::hasTable('consultas_triage')) {
                $query = ConsultaTriage::with(['especialidadRecomendada', 'medicoRecomendado'])
                    ->orderByDesc('fecha_registro')
                    ->limit(6);

                if ($usuario->tieneRol('Paciente')) {
                    $query->where(function ($q) use ($usuario) {
                        $q->where('id_usuario', $usuario->id_usuario);
                        if ($usuario->id_paciente) {
                            $q->orWhere('id_paciente', $usuario->id_paciente);
                        }
                    });
                }

                $historialReciente = $query->get();
            }
        } catch (Throwable) {
            // Mantener degradación elegante si la BD aún no fue inicializada
        }

        return view('triage.index', [
            'usuario' => $usuario,
            'proveedorActivo' => $proveedorActivo,
            'totalEspecialidades' => $totalEspecialidades,
            'totalMedicos' => $totalMedicos,
            'historialReciente' => $historialReciente,
        ]);
    }

    /**
     * Procesa el texto libre de síntomas mediante NLP vectorial + matching de criterios + alertas rojas.
     */
    public function analizar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'sintomas' => ['required', 'string', 'min:6', 'max:1500'],
            'id_paciente' => ['nullable', 'integer'],
        ], [
            'sintomas.required' => 'Por favor describe brevemente los síntomas que sientes.',
            'sintomas.min' => 'Describe un poco más tus síntomas (al menos 6 caracteres) para recomendarte el especialista adecuado.',
        ]);

        $usuario = $request->user();
        $idUsuario = $usuario?->id_usuario;
        $idPaciente = $usuario?->id_paciente
            ?? (isset($datos['id_paciente']) ? (int) $datos['id_paciente'] : null);

        $resultado = $this->triageService->analizarSintomas(
            $datos['sintomas'],
            $idUsuario,
            $idPaciente
        );

        return response()->json([
            'success' => true,
            'message' => $resultado['es_emergencia']
                ? 'Alerta Roja detectada: se activó el protocolo de urgencias y se notificó al Administrador.'
                : 'Análisis clínico preliminar completado con éxito.',
            'data' => $resultado,
        ]);
    }

    /**
     * Endpoint de polling en tiempo real para mostrar alertas rojas en pantalla del Administrador / Recepcionista.
     */
    public function alertasActivas(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $rolSesion = $request->hasSession() ? $request->session()->get('rol_activo') : null;
        $rolEfectivo = $rolSesion ?: $usuario?->nombreRol();

        if (! in_array($rolEfectivo, ['Administrador', 'Recepcionista'], true)) {
            return response()->json([
                'success' => true,
                'total' => 0,
                'data' => [],
            ]);
        }

        $alertas = $this->triageService->obtenerAlertasActivas();

        return response()->json([
            'success' => true,
            'total' => count($alertas),
            'data' => $alertas,
        ]);
    }

    /**
     * Panel de monitoreo de Alertas Rojas, historial de Triage IA y gestión de la base vectorial JSONB.
     */
    public function panelAlertas(): View
    {
        $alertasActivas = $this->triageService->obtenerAlertasActivas(30);
        $consultas = collect();
        $medicosGuardia = collect();
        $totalPerfilesVectorizados = 0;
        $kpis = [
            'total_consultas' => 0,
            'emergencias_activas' => count($alertasActivas),
            'emergencias_totales' => 0,
            'afinidad_promedio' => 0.0,
        ];

        try {
            $medicosGuardia = Medico::where('estado', true)
                ->with('especialidades')
                ->orderBy('nombre_completo')
                ->get();

            if (Schema::hasTable('consultas_triage')) {
                $consultas = ConsultaTriage::with(['usuario', 'paciente', 'especialidadRecomendada', 'medicoRecomendado', 'usuarioAtendio'])
                    ->orderByDesc('fecha_registro')
                    ->paginate(20);

                $kpis['total_consultas'] = ConsultaTriage::count();
                $kpis['emergencias_totales'] = ConsultaTriage::where('es_emergencia', true)->count();
                $kpis['afinidad_promedio'] = round((float) (ConsultaTriage::avg('puntaje_afinidad') ?? 0), 1);
            }

            if (Schema::hasTable('perfiles_clinicos_medicos')) {
                $totalPerfilesVectorizados = PerfilClinicoMedico::where('activo', true)->count();
            }
        } catch (Throwable) {
            // Degradación segura
        }

        return view('triage.alertas', [
            'alertasActivas' => $alertasActivas,
            'consultas' => $consultas,
            'kpis' => $kpis,
            'proveedorActivo' => $this->embeddingProvider->proveedorActivo(),
            'totalPerfilesVectorizados' => $totalPerfilesVectorizados,
            'medicosGuardia' => $medicosGuardia,
            'accionesDerivacion' => ConsultaTriage::ACCIONES_DERIVACION,
        ]);
    }

    /**
     * Marca una alerta roja de emergencia como atendida/despachada con derivación manual.
     */
    public function atenderAlerta(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'tipo_derivacion' => ['nullable', 'string', 'in:' . implode(',', array_keys(ConsultaTriage::ACCIONES_DERIVACION))],
            'id_medico_asignado' => ['nullable', 'integer'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        $idUsuario = (int) ($request->user()?->id_usuario ?? 1);
        $consulta = $this->triageService->marcarAlertaAtendida(
            $id,
            $idUsuario,
            $datos['tipo_derivacion'] ?? null,
            isset($datos['id_medico_asignado']) ? (int) $datos['id_medico_asignado'] : null,
            $datos['observacion'] ?? null
        );
        $resolucion = $consulta?->resolucionManual();
        $etiqueta = $resolucion['etiqueta_derivacion'] ?? 'Alerta atendida';

        return response()->json([
            'success' => true,
            'message' => "Alerta Roja #{$id} resuelta: {$etiqueta}.",
            'data' => [
                'id_triage' => $consulta?->id_triage ?? $id,
                'alerta_atendida' => true,
                'resolucion_manual' => $resolucion,
            ],
        ]);
    }

    /**
     * Reconstruye y reindexa todos los vectores JSONB en `perfiles_clinicos_medicos`.
     */
    public function reindexar(): JsonResponse
    {
        $resultado = $this->knowledgeBase->sincronizarPerfiles(true);

        return response()->json([
            'success' => true,
            'message' => "Base vectorial clínica reindexada: {$resultado['sincronizados']} perfiles actualizados con proveedor {$resultado['proveedor']}.",
            'data' => $resultado,
        ]);
    }
}
