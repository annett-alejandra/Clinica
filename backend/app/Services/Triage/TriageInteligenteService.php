<?php

namespace App\Services\Triage;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P2_PersonalMedico\PerfilClinicoMedico;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\Paquetes\P4_GestionCitas\ConsultaTriage;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use App\Services\Paquetes\P4_GestionCitas\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Orquestador principal del Sistema de Asignación Inteligente de Médicos
 * basado en Síntomas y Especialidades (NLP Vectorial + Matching de Criterios + Alertas Rojas).
 */
class TriageInteligenteService
{
    public function __construct(
        protected VectorMathService $vectorMath,
        protected EmbeddingProviderService $embeddingProvider,
        protected EmergencyDetectorService $emergencyDetector,
        protected KnowledgeBaseSyncService $knowledgeBase,
        protected DisponibilidadService $disponibilidadService
    ) {}

    /**
     * Analiza los síntomas escritos en lenguaje natural, detecta emergencias,
     * calcula el scoring vectorial + multicriterio por especialista y persiste trazabilidad.
     *
     * @return array<string, mixed>
     */
    public function analizarSintomas(string $sintomasTexto, ?int $idUsuario = null, ?int $idPaciente = null): array
    {
        $sintomasLimpio = trim($sintomasTexto);

        // 1. Detección inmediata de Banderas Rojas / Emergencia Clínica
        $emergencia = $this->emergencyDetector->evaluar($sintomasLimpio);

        // 2. Generación de vector semántico de la consulta (API externa o motor local híbrido)
        $embeddingConsulta = $this->embeddingProvider->generarEmbedding($sintomasLimpio);
        $vectorLocalConsulta = $embeddingConsulta['tipo'] === 'sparse'
            ? $embeddingConsulta['vector']
            : $this->vectorMath->construirVectorLocal($sintomasLimpio);

        // 3. Cargar catálogo activo de médicos, especialidades, horarios y efectividad histórica
        $medicos = Medico::where('estado', true)
            ->with([
                'especialidades' => fn ($q) => $q->where('estado', true),
                'horariosAtencion',
            ])
            ->orderBy('nombre_completo')
            ->get();

        $perfilesPersistidos = $this->cargarPerfilesPersistidos();
        $efectividadPorMedico = $this->calcularEfectividadHistorica();

        $textoConsultaNorm = $this->vectorMath->normalizarTexto($sintomasLimpio);
        $tokensConsulta = $this->vectorMath->extraerPalabrasClave($textoConsultaNorm);
        $tokensExpandidos = $this->expandirTokensConSinonimos($tokensConsulta);

        $pesos = config('triage.scoring_weights', [
            'vectorial' => 0.55,
            'criterios' => 0.25,
            'efectividad' => 0.12,
            'disponibilidad' => 0.08,
        ]);

        $candidatos = [];

        foreach ($medicos as $medico) {
            foreach ($medico->especialidades as $especialidad) {
                $clave = "{$medico->id_medico}:{$especialidad->id_especialidad}";
                $perfil = $perfilesPersistidos[$clave]
                    ?? $this->knowledgeBase->construirPerfilEnMemoria($medico, $especialidad);

                // A. Similitud Vectorial Coseno (densa si proveedor coincide; local dispersa si no)
                $vectorPerfil = $perfil['vector_embedding'] ?? [];
                $mismoProveedorDenso = $embeddingConsulta['tipo'] === 'dense'
                    && ($perfil['proveedor_embedding'] ?? '') === $embeddingConsulta['provider']
                    && array_is_list($vectorPerfil);

                if ($mismoProveedorDenso) {
                    $simVectorial = $this->vectorMath->similitudCoseno($embeddingConsulta['vector'], $vectorPerfil);
                    // Rescalado calibrado para embeddings densos (típico 0.28..0.82 -> 0..1)
                    $scoreVectorial = max(0.0, min(1.0, ($simVectorial - 0.22) / 0.58));
                } else {
                    $vectorPerfilLocal = ! array_is_list($vectorPerfil) && $vectorPerfil !== []
                        ? $vectorPerfil
                        : $this->vectorMath->construirVectorLocal(
                            (string) ($perfil['perfil_textual'] ?? $especialidad->nombre_especialidad),
                            array_merge($perfil['subespecialidades'] ?? [], $perfil['condiciones_tratadas'] ?? [])
                        );
                    $simVectorial = $this->vectorMath->similitudCoseno($vectorLocalConsulta, $vectorPerfilLocal);
                    // Curva suave sqrt para vectores dispersos cortos (evita subestimación por vocabulario breve)
                    $scoreVectorial = max(0.0, min(1.0, pow($simVectorial, 0.52) * 1.18));
                }

                // B. Matching de Criterios Clínicos Estructurados (etiquetas, condiciones y subespecialidades)
                $matchingCriterios = $this->evaluarCriteriosClinicos(
                    $textoConsultaNorm,
                    $tokensExpandidos,
                    $especialidad,
                    $perfil['condiciones_tratadas'] ?? [],
                    $perfil['subespecialidades'] ?? []
                );
                $scoreCriterios = $matchingCriterios['score'];

                // C. Efectividad Histórica del Médico (real de citas atendidas + suavizado bayesiano)
                $statsMed = $efectividadPorMedico[$medico->id_medico] ?? ['atendidas' => 0, 'total' => 0];
                $tasaBase = (float) ($perfil['tasa_efectividad_base'] ?? 92.0);
                $tasaEfectividad = $this->calcularEfectividadCombinada($statsMed['atendidas'], $statsMed['total'], $tasaBase);
                $scoreEfectividad = max(0.0, min(1.0, $tasaEfectividad / 100.0));

                // D. Disponibilidad Operativa (horarios activos)
                $tieneHorarios = $medico->horariosAtencion->isNotEmpty();
                $scoreDisponibilidad = $tieneHorarios ? 1.0 : 0.35;

                // Combinación Ponderada + Calibración de Porcentaje de Afinidad (0 - 99%)
                $relevanciaClinica = ($scoreVectorial * 0.65) + ($scoreCriterios * 0.35);
                $scoreBruto = ($scoreVectorial * $pesos['vectorial'])
                    + ($scoreCriterios * $pesos['criterios'])
                    + ($scoreEfectividad * $pesos['efectividad'])
                    + ($scoreDisponibilidad * $pesos['disponibilidad']);

                // Si no hay coincidencia clínica real, acotar afinidad para que Medicina Interna actúe como respaldo prudente
                if ($relevanciaClinica < 0.08) {
                    $esMedicinaInterna = str_contains(
                        $this->vectorMath->normalizarTexto((string) $especialidad->nombre_especialidad),
                        'medicina interna'
                    );
                    $porcentajeAfinidad = $esMedicinaInterna ? 68.0 : round(max(18.0, $scoreBruto * 100.0), 1);
                } else {
                    $porcentajeAfinidad = round(min(98.5, max(35.0, (0.48 + $scoreBruto * 0.56) * 100.0)), 1);
                }

                $candidatos[] = [
                    'medico' => [
                        'id_medico' => $medico->id_medico,
                        'nombre_completo' => $medico->nombre_completo,
                        'registro_profesional' => $medico->registro_profesional,
                        'correo' => $medico->correo,
                        'telefono' => $medico->telefono,
                    ],
                    'especialidad' => [
                        'id_especialidad' => $especialidad->id_especialidad,
                        'nombre_especialidad' => $especialidad->nombre_especialidad,
                        'descripcion' => $especialidad->descripcion,
                    ],
                    'subespecialidades' => $perfil['subespecialidades'] ?? [],
                    'porcentaje_afinidad' => $porcentajeAfinidad,
                    'desglose' => [
                        'similitud_vectorial_pct' => round($scoreVectorial * 100, 1),
                        'matching_criterios_pct' => round($scoreCriterios * 100, 1),
                        'efectividad_historica_pct' => round($tasaEfectividad, 1),
                        'disponibilidad_pct' => round($scoreDisponibilidad * 100, 1),
                        'citas_atendidas' => $statsMed['atendidas'],
                    ],
                    'criterios_coincidentes' => $matchingCriterios['coincidencias'],
                    'proximo_cupo' => null,
                ];
            }
        }

        // Ordenar por afinidad descendente y desempate por coincidencia clínica y efectividad
        usort($candidatos, function (array $a, array $b): int {
            return ($b['porcentaje_afinidad'] <=> $a['porcentaje_afinidad'])
                ?: ($b['desglose']['matching_criterios_pct'] <=> $a['desglose']['matching_criterios_pct'])
                ?: ($b['desglose']['efectividad_historica_pct'] <=> $a['desglose']['efectividad_historica_pct']);
        });

        // Tomar los mejores 4 especialistas y consultar su próximo cupo real en CU10
        $topRecomendaciones = array_slice($candidatos, 0, 4);
        foreach ($topRecomendaciones as &$rec) {
            $rec['proximo_cupo'] = $this->buscarProximoCupoDisponible(
                (int) $rec['especialidad']['id_especialidad'],
                (int) $rec['medico']['id_medico']
            );
            $rec['explicacion_individual'] = $this->construirExplicacionIndividual($rec, $emergencia);
        }
        unset($rec);

        $principal = $topRecomendaciones[0] ?? null;

        // 4. Explicación natural (LLM externo si está configurado, sino motor explicativo clínico local)
        $explicacionPrincipal = null;
        if ($principal !== null) {
            $explicacionPrincipal = $this->embeddingProvider->generarExplicacionLlm($sintomasLimpio, $principal, $emergencia)
                ?? $this->construirExplicacionClinicaPrincipal($principal, $emergencia);
        }

        // 5. Persistir en consultas_triage y registrar en auditoria (incluyendo Alerta Roja si aplica)
        $consultaPersistida = $this->persistirConsulta(
            $idUsuario,
            $idPaciente,
            $sintomasLimpio,
            $embeddingConsulta,
            $emergencia,
            $principal,
            $explicacionPrincipal,
            $topRecomendaciones
        );

        return [
            'id_triage' => $consultaPersistida?->id_triage,
            'sintomas_analizados' => $sintomasLimpio,
            'proveedor_ia' => $embeddingConsulta['provider'],
            'modo_vectorial' => $embeddingConsulta['tipo'] === 'dense'
                ? 'Embeddings Densos ('.strtoupper($embeddingConsulta['provider']).') + Coseno'
                : 'NLP Vectorial Local TF-IDF + Ontología Clínica + Coseno',
            'es_emergencia' => $emergencia['es_emergencia'],
            'nivel_urgencia' => $emergencia['nivel_urgencia'],
            'palabras_emergencia_detectadas' => $emergencia['palabras_detectadas'],
            'motivos_alerta' => $emergencia['motivos_alerta'],
            'protocolo_emergencia' => $emergencia['protocolo'],
            'alerta_admin_enviada' => $emergencia['es_emergencia'],
            'recomendacion_principal' => $principal,
            'explicacion_clinica' => $explicacionPrincipal ?? 'No se encontraron médicos activos registrados en el sistema para generar una recomendación.',
            'recomendaciones' => $topRecomendaciones,
            'aviso_legal' => 'Esta recomendación es un triage clínico preliminar asistido por IA para orientar la selección de especialidad. No sustituye un diagnóstico médico presencial.',
            'fecha_analisis' => now()->timezone('America/La_Paz')->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Devuelve las alertas rojas de emergencia activas (no atendidas) para el panel de Administración.
     *
     * @return list<array<string, mixed>>
     */
    public function obtenerAlertasActivas(int $limite = 15): array
    {
        if (! $this->tablaTriageDisponible()) {
            return [];
        }

        try {
            $alertas = ConsultaTriage::with(['usuario', 'paciente', 'especialidadRecomendada', 'medicoRecomendado'])
                ->where('es_emergencia', true)
                ->where('alerta_atendida', false)
                ->orderByDesc('fecha_registro')
                ->limit($limite)
                ->get();

            return $alertas->map(function (ConsultaTriage $a): array {
                return [
                    'id_triage' => $a->id_triage,
                    'id_paciente' => $a->id_paciente,
                    'paciente_nombre' => $a->paciente?->nombre_completo
                        ?? $a->usuario?->nombreVisible()
                        ?? 'Visitante / Paciente en Portal',
                    'paciente_ci' => $a->paciente?->ci,
                    'paciente_telefono' => $a->paciente?->telefono,
                    'sintomas_texto' => $a->sintomas_texto,
                    'palabras_detectadas' => $a->palabras_emergencia_detectadas ?? [],
                    'id_especialidad_sugerida' => $a->id_especialidad_recomendada,
                    'especialidad_sugerida' => $a->especialidadRecomendada?->nombre_especialidad ?? 'Urgencias / Cardiología',
                    'id_medico_sugerido' => $a->id_medico_recomendado,
                    'medico_sugerido' => $a->medicoRecomendado?->nombre_completo,
                    'puntaje_afinidad' => $a->puntaje_afinidad,
                    'fecha_registro' => $a->fecha_registro
                        ? Carbon::parse($a->fecha_registro)->timezone('America/La_Paz')->format('d/m/Y H:i:s')
                        : now()->timezone('America/La_Paz')->format('d/m/Y H:i:s'),
                    'hace' => $a->fecha_registro
                        ? Carbon::parse($a->fecha_registro)->diffForHumans()
                        : 'Hace instantes',
                ];
            })->all();
        } catch (Throwable $e) {
            Log::warning('Error consultando alertas activas de triage: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Marca una alerta roja de triage como atendida / despachada y registra la derivación manual.
     */
    public function marcarAlertaAtendida(
        int $idTriage,
        int $idUsuario,
        ?string $tipoDerivacion = null,
        ?int $idMedicoAsignado = null,
        ?string $observacion = null
    ): ?ConsultaTriage {
        if (! $this->tablaTriageDisponible()) {
            return null;
        }

        $consulta = ConsultaTriage::findOrFail($idTriage);
        $tipoFinal = array_key_exists((string) $tipoDerivacion, ConsultaTriage::ACCIONES_DERIVACION)
            ? (string) $tipoDerivacion
            : 'DERIVADO_URGENCIAS';
        $etiqueta = ConsultaTriage::ACCIONES_DERIVACION[$tipoFinal];

        $medicoAsignado = $idMedicoAsignado
            ? Medico::find($idMedicoAsignado)
            : ($consulta->id_medico_recomendado ? Medico::find($consulta->id_medico_recomendado) : null);

        if ($idMedicoAsignado && $medicoAsignado) {
            $consulta->id_medico_recomendado = $medicoAsignado->id_medico;
        }

        $actual = $consulta->recomendaciones_json ?? [];
        $candidatos = (is_array($actual) && array_key_exists('candidatos', $actual))
            ? $actual['candidatos']
            : (is_array($actual) ? $actual : []);

        $observacionFinal = trim((string) $observacion) !== ''
            ? trim((string) $observacion)
            : 'Alerta gestionada y cerrada manualmente por personal operativo.';

        $consulta->recomendaciones_json = [
            'candidatos' => $candidatos,
            'resolucion_manual' => [
                'tipo_derivacion' => $tipoFinal,
                'etiqueta_derivacion' => $etiqueta,
                'id_medico_asignado' => $medicoAsignado?->id_medico,
                'medico_asignado' => $medicoAsignado?->nombre_completo,
                'observacion' => $observacionFinal,
                'fecha_resolucion' => now('America/La_Paz')->format('d/m/Y H:i'),
            ],
        ];

        $consulta->alerta_atendida = true;
        $consulta->id_usuario_atendio = $idUsuario;
        $consulta->fecha_atencion_alerta = now('America/La_Paz');
        $consulta->save();

        AuditService::registrar(
            $idUsuario,
            'ALERTA_ROJA_TRIAGE_ATENDIDA',
            'consultas_triage',
            $consulta->id_triage,
            [
                'nivel_urgencia' => $consulta->nivel_urgencia,
                'id_paciente' => $consulta->id_paciente,
                'palabras_detectadas' => $consulta->palabras_emergencia_detectadas,
                'tipo_derivacion' => $tipoFinal,
                'etiqueta_derivacion' => $etiqueta,
                'medico_asignado' => $medicoAsignado?->nombre_completo,
                'observacion' => $observacionFinal,
            ]
        );

        return $consulta;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function cargarPerfilesPersistidos(): array
    {
        if (! $this->knowledgeBase->tablaPerfilesDisponible()) {
            return [];
        }

        try {
            $perfiles = PerfilClinicoMedico::where('activo', true)->get();

            // Si la tabla existe pero aún está vacía, sincronizar automáticamente la primera vez
            if ($perfiles->isEmpty()) {
                $this->knowledgeBase->sincronizarPerfiles(false);
                $perfiles = PerfilClinicoMedico::where('activo', true)->get();
            }

            $mapa = [];
            foreach ($perfiles as $p) {
                $mapa["{$p->id_medico}:{$p->id_especialidad}"] = [
                    'subespecialidades' => $p->subespecialidades ?? [],
                    'condiciones_tratadas' => $p->condiciones_tratadas ?? [],
                    'perfil_textual' => $p->perfil_textual,
                    'vector_embedding' => $p->vector_embedding ?? [],
                    'proveedor_embedding' => $p->proveedor_embedding,
                    'tasa_efectividad_base' => $p->tasa_efectividad_base,
                ];
            }

            return $mapa;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Calcula citas atendidas (estado 3) y total evaluable (excluyendo canceladas estado 4) por médico.
     *
     * @return array<int, array{atendidas: int, total: int}>
     */
    private function calcularEfectividadHistorica(): array
    {
        try {
            $rows = CitaMedica::select(
                'id_medico',
                DB::raw('COUNT(*) FILTER (WHERE id_estado_cita = 3) as atendidas'),
                DB::raw('COUNT(*) FILTER (WHERE id_estado_cita IN (3, 5)) as evaluables')
            )
                ->groupBy('id_medico')
                ->get();

            $mapa = [];
            foreach ($rows as $row) {
                $mapa[(int) $row->id_medico] = [
                    'atendidas' => (int) $row->atendidas,
                    'total' => (int) $row->evaluables,
                ];
            }

            return $mapa;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Suavizado bayesiano entre historial real de efectividad y efectividad base del especialista.
     */
    private function calcularEfectividadCombinada(int $atendidas, int $totalEvaluables, float $tasaBase): float
    {
        $pesoPrior = 5.0; // equivalente a 5 citas base para evitar extremos con historial pequeño
        $efectividad = (($atendidas + ($pesoPrior * ($tasaBase / 100.0))) / ($totalEvaluables + $pesoPrior)) * 100.0;

        return round(max(60.0, min(99.5, $efectividad)), 2);
    }

    /**
     * Evalúa coincidencia exacta y semántica contra condiciones tratadas, subespecialidades y nombre de especialidad.
     *
     * @param  list<string>  $tokensExpandidos
     * @param  list<string>  $condiciones
     * @param  list<string>  $subespecialidades
     * @return array{score: float, coincidencias: list<string>}
     */
    private function evaluarCriteriosClinicos(
        string $textoConsultaNorm,
        array $tokensExpandidos,
        Especialidad $especialidad,
        array $condiciones,
        array $subespecialidades
    ): array {
        $coincidencias = [];
        $puntos = 0.0;

        $espNorm = $this->vectorMath->normalizarTexto((string) $especialidad->nombre_especialidad);
        if ($espNorm !== '' && str_contains($textoConsultaNorm, $espNorm)) {
            $puntos += 3.0;
            $coincidencias[] = $especialidad->nombre_especialidad;
        }

        $setTokens = array_flip($tokensExpandidos);

        foreach ($condiciones as $condicion) {
            $condNorm = $this->vectorMath->normalizarTexto((string) $condicion);
            if ($condNorm === '') {
                continue;
            }

            // Coincidencia de frase completa de la condición
            if (str_contains($textoConsultaNorm, $condNorm)) {
                $puntos += 2.6;
                $coincidencias[] = (string) $condicion;
                continue;
            }

            // Coincidencia por tokens clave de la condición
            $tokensCond = $this->vectorMath->extraerPalabrasClave($condNorm);
            if ($tokensCond === []) {
                continue;
            }

            $hits = 0;
            foreach ($tokensCond as $tc) {
                if (isset($setTokens[$tc])) {
                    $hits++;
                }
            }

            $proporcion = $hits / count($tokensCond);
            if ($proporcion >= 0.5) {
                $puntos += 1.4 * $proporcion;
                $coincidencias[] = (string) $condicion;
            }
        }

        foreach ($subespecialidades as $sub) {
            $subNorm = $this->vectorMath->normalizarTexto((string) $sub);
            $tokensSub = $this->vectorMath->extraerPalabrasClave($subNorm);
            foreach ($tokensSub as $ts) {
                if (isset($setTokens[$ts])) {
                    $puntos += 0.9;
                    $coincidencias[] = (string) $sub;
                    break;
                }
            }
        }

        $coincidencias = array_values(array_unique($coincidencias));
        // Función de saturación suave: 4.5 puntos de coincidencia ya representan ~95%+ de matching de criterios
        $score = 1.0 - exp(-$puntos / 2.6);

        return [
            'score' => max(0.0, min(1.0, $score)),
            'coincidencias' => array_slice($coincidencias, 0, 5),
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private function expandirTokensConSinonimos(array $tokens): array
    {
        $sinonimos = config('triage.sinonimos_clinicos', []);
        $expandidos = $tokens;

        foreach ($tokens as $tok) {
            if (isset($sinonimos[$tok])) {
                foreach ($sinonimos[$tok] as $sin) {
                    $sinNorm = $this->vectorMath->normalizarTexto((string) $sin);
                    foreach ($this->vectorMath->extraerPalabrasClave($sinNorm) as $st) {
                        $expandidos[] = $st;
                    }
                }
            }
        }

        return array_values(array_unique($expandidos));
    }

    /**
     * Busca el próximo turno disponible real del médico en los siguientes 6 días (CU10).
     *
     * @return array{fecha: string, fecha_legible: string, hora: string}|null
     */
    private function buscarProximoCupoDisponible(int $idEspecialidad, int $idMedico): ?array
    {
        try {
            $hoy = Carbon::now('America/La_Paz')->startOfDay();
            for ($offset = 0; $offset < 6; $offset++) {
                $fecha = $hoy->copy()->addDays($offset);
                $oferta = $this->disponibilidadService->consultar($idEspecialidad, $idMedico, $fecha->format('Y-m-d'));

                if (! empty($oferta) && ! empty($oferta[0]['cupos'])) {
                    $primerCupo = $oferta[0]['cupos'][0];
                    $hora = is_array($primerCupo) ? ($primerCupo['hora_inicio'] ?? '') : (string) $primerCupo;
                    if ($hora !== '') {
                        return [
                            'fecha' => $fecha->format('Y-m-d'),
                            'fecha_legible' => $fecha->format('d/m/Y'),
                            'hora' => substr($hora, 0, 5),
                        ];
                    }
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function construirExplicacionClinicaPrincipal(array $principal, array $emergencia): string
    {
        $esp = $principal['especialidad']['nombre_especialidad'];
        $med = $principal['medico']['nombre_completo'];
        $afinidad = $principal['porcentaje_afinidad'];
        $criterios = $principal['criterios_coincidentes'] ?? [];
        $efectividad = $principal['desglose']['efectividad_historica_pct'] ?? 92.0;

        $fragmentoCriterios = $criterios !== []
            ? 'por su estrecha relación clínica con '.implode(', ', array_slice($criterios, 0, 3))
            : 'por la similitud semántica de sus síntomas con esta área clínica';

        if ($emergencia['es_emergencia']) {
            return "ATENCIÓN PRIORITARIA: Se detectaron signos de alarma que requieren evaluación inmediata en Urgencias 24/7. Para seguimiento especializado, se sugiere {$esp} ({$afinidad}% de afinidad) con {$med}, {$fragmentoCriterios}.";
        }

        $texto = "Recomendamos {$esp} ({$afinidad}% de afinidad) con {$med}, {$fragmentoCriterios}. ";
        $texto .= "El especialista cuenta con una efectividad clínica registrada del {$efectividad}%";

        if (! empty($principal['proximo_cupo'])) {
            $texto .= " y dispone de su próximo turno libre el {$principal['proximo_cupo']['fecha_legible']} a las {$principal['proximo_cupo']['hora']} hrs.";
        } else {
            $texto .= ' y atiende las subespecialidades vinculadas a tu consulta.';
        }

        return $texto;
    }

    private function construirExplicacionIndividual(array $rec, array $emergencia): string
    {
        $criterios = $rec['criterios_coincidentes'] ?? [];
        if ($criterios !== []) {
            return 'Coincidencia clínica directa en: '.implode(', ', array_slice($criterios, 0, 3)).'.';
        }

        if ($emergencia['es_emergencia']) {
            return 'Especialista de respaldo sugerido tras estabilización en Urgencias 24/7.';
        }

        return 'Evaluación recomendada por afinidad semántica con su especialidad y trayectoria clínica.';
    }

    private function persistirConsulta(
        ?int $idUsuario,
        ?int $idPaciente,
        string $sintomasTexto,
        array $embeddingConsulta,
        array $emergencia,
        ?array $principal,
        ?string $explicacion,
        array $topRecomendaciones
    ): ?ConsultaTriage {
        $registro = null;

        if ($this->tablaTriageDisponible()) {
            try {
                $registro = ConsultaTriage::create([
                    'id_usuario' => $idUsuario,
                    'id_paciente' => $idPaciente,
                    'sintomas_texto' => $sintomasTexto,
                    'vector_consulta' => $embeddingConsulta['vector'],
                    'proveedor_ia' => $embeddingConsulta['provider'],
                    'nivel_urgencia' => $emergencia['nivel_urgencia'],
                    'es_emergencia' => $emergencia['es_emergencia'],
                    'palabras_emergencia_detectadas' => $emergencia['palabras_detectadas'],
                    'id_especialidad_recomendada' => $principal['especialidad']['id_especialidad'] ?? null,
                    'id_medico_recomendado' => $principal['medico']['id_medico'] ?? null,
                    'puntaje_afinidad' => $principal['porcentaje_afinidad'] ?? null,
                    'explicacion_clinica' => $explicacion,
                    'recomendaciones_json' => $topRecomendaciones,
                    'alerta_atendida' => ! $emergencia['es_emergencia'],
                ]);
            } catch (Throwable $e) {
                Log::warning('No se pudo persistir consulta_triage (se continúa con la respuesta): '.$e->getMessage());
            }
        }

        // Registrar trazabilidad en auditoria (y disparar evento de ALERTA ROJA si es emergencia)
        AuditService::registrar(
            $idUsuario,
            $emergencia['es_emergencia'] ? 'ALERTA_ROJA_TRIAGE' : 'TRIAGE_IA_CONSULTA',
            'consultas_triage',
            $registro?->id_triage,
            [
                'nivel_urgencia' => $emergencia['nivel_urgencia'],
                'es_emergencia' => $emergencia['es_emergencia'],
                'proveedor_ia' => $embeddingConsulta['provider'],
                'especialidad_recomendada' => $principal['especialidad']['nombre_especialidad'] ?? null,
                'puntaje_afinidad' => $principal['porcentaje_afinidad'] ?? null,
                'palabras_emergencia' => $emergencia['palabras_detectadas'],
            ]
        );

        return $registro;
    }

    private function tablaTriageDisponible(): bool
    {
        try {
            return Schema::hasTable('consultas_triage');
        } catch (Throwable) {
            return false;
        }
    }
}
