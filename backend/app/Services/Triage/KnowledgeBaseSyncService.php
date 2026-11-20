<?php

namespace App\Services\Triage;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P2_PersonalMedico\PerfilClinicoMedico;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Construye, sincroniza y vectoriza la base de conocimientos médica estructurada
 * (perfiles clínicos de médicos, especialidades, subespecialidades y condiciones tratadas)
 * almacenando sus vectores semánticos en formato JSONB dentro de PostgreSQL.
 */
class KnowledgeBaseSyncService
{
    public function __construct(
        protected VectorMathService $vectorMath,
        protected EmbeddingProviderService $embeddingProvider
    ) {}

    /**
     * Sincroniza todos los médicos activos y sus especialidades con `perfiles_clinicos_medicos`.
     *
     * @return array{sincronizados: int, proveedor: string}
     */
    public function sincronizarPerfiles(bool $forzarReindex = false): array
    {
        if (! $this->tablaPerfilesDisponible()) {
            return ['sincronizados' => 0, 'proveedor' => $this->embeddingProvider->proveedorActivo()];
        }

        $proveedorActual = $this->embeddingProvider->proveedorActivo();
        $medicos = Medico::where('estado', true)
            ->with(['especialidades' => fn ($q) => $q->where('estado', true)])
            ->get();

        $sincronizados = 0;

        foreach ($medicos as $medico) {
            foreach ($medico->especialidades as $especialidad) {
                $existente = PerfilClinicoMedico::where('id_medico', $medico->id_medico)
                    ->where('id_especialidad', $especialidad->id_especialidad)
                    ->first();

                if ($existente && ! $forzarReindex && $existente->vector_embedding && $existente->proveedor_embedding === $proveedorActual) {
                    continue;
                }

                $base = $this->construirDatosBasePerfil($medico, $especialidad);
                $embedding = $this->embeddingProvider->generarEmbedding(
                    $base['perfil_textual'],
                    array_merge($base['subespecialidades'], $base['condiciones_tratadas'])
                );

                PerfilClinicoMedico::updateOrCreate(
                    [
                        'id_medico' => $medico->id_medico,
                        'id_especialidad' => $especialidad->id_especialidad,
                    ],
                    [
                        'subespecialidades' => $base['subespecialidades'],
                        'condiciones_tratadas' => $base['condiciones_tratadas'],
                        'perfil_textual' => $base['perfil_textual'],
                        'vector_embedding' => $embedding['vector'],
                        'proveedor_embedding' => $embedding['provider'],
                        'tasa_efectividad_base' => $existente?->tasa_efectividad_base ?? $base['tasa_efectividad_base'],
                        'activo' => true,
                    ]
                );

                $sincronizados++;
            }
        }

        return [
            'sincronizados' => $sincronizados,
            'proveedor' => $proveedorActual,
        ];
    }

    /**
     * Construye los metadatos clínicos enriquecidos y el texto base de embedding para un (Médico, Especialidad).
     *
     * @return array{
     *     subespecialidades: list<string>,
     *     condiciones_tratadas: list<string>,
     *     perfil_textual: string,
     *     tasa_efectividad_base: float
     * }
     */
    public function construirDatosBasePerfil(Medico $medico, Especialidad $especialidad): array
    {
        $nombreNorm = $this->vectorMath->normalizarTexto((string) $especialidad->nombre_especialidad);
        $catalogo = config('triage.catalogo_especialidades', []);

        $entradaCatalogo = null;
        foreach ($catalogo as $clave => $datos) {
            if (str_contains($nombreNorm, $this->vectorMath->normalizarTexto((string) $clave))) {
                $entradaCatalogo = $datos;
                break;
            }
        }

        $subespecialidades = $entradaCatalogo['subespecialidades']
            ?? [$especialidad->nombre_especialidad.' Clínica', 'Consulta Especializada y Seguimiento'];

        $condiciones = $entradaCatalogo['condiciones']
            ?? $this->extraerCondicionesDesdeDescripcion((string) $especialidad->nombre_especialidad, (string) ($especialidad->descripcion ?? ''));

        $perfilBase = $entradaCatalogo['perfil_base']
            ?? trim($especialidad->nombre_especialidad.'. '.($especialidad->descripcion ?? 'Evaluación clínica y diagnóstico especializado.'));

        $perfilTextual = sprintf(
            'Especialidad: %s. Descripción: %s. Subespecialidades: %s. Condiciones y síntomas tratados: %s. Perfil clínico: %s',
            $especialidad->nombre_especialidad,
            $especialidad->descripcion ?? 'Atención médica especializada',
            implode(', ', $subespecialidades),
            implode(', ', $condiciones),
            $perfilBase
        );

        // Efectividad base realista diferenciada determinísticamente por médico (91.0 - 97.5%)
        $variacion = (($medico->id_medico * 13 + $especialidad->id_especialidad * 7) % 65) / 10;
        $efectividadBase = round(91.0 + $variacion, 2);

        return [
            'subespecialidades' => array_values($subespecialidades),
            'condiciones_tratadas' => array_values($condiciones),
            'perfil_textual' => $perfilTextual,
            'tasa_efectividad_base' => $efectividadBase,
        ];
    }

    /**
     * Genera un perfil completo en memoria (fallback si la tabla aún no fue migrada o sincronizada).
     */
    public function construirPerfilEnMemoria(Medico $medico, Especialidad $especialidad): array
    {
        $base = $this->construirDatosBasePerfil($medico, $especialidad);
        $vectorLocal = $this->vectorMath->construirVectorLocal(
            $base['perfil_textual'],
            array_merge($base['subespecialidades'], $base['condiciones_tratadas'])
        );

        return [
            'subespecialidades' => $base['subespecialidades'],
            'condiciones_tratadas' => $base['condiciones_tratadas'],
            'perfil_textual' => $base['perfil_textual'],
            'vector_embedding' => $vectorLocal,
            'proveedor_embedding' => 'local_tfidf',
            'tasa_efectividad_base' => $base['tasa_efectividad_base'],
        ];
    }

    public function tablaPerfilesDisponible(): bool
    {
        try {
            return Schema::hasTable('perfiles_clinicos_medicos');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function extraerCondicionesDesdeDescripcion(string $nombreEspecialidad, string $descripcion): array
    {
        $tokens = $this->vectorMath->extraerPalabrasClave(
            $this->vectorMath->normalizarTexto($nombreEspecialidad.' '.$descripcion)
        );

        return array_values(array_unique(array_merge([mb_strtolower($nombreEspecialidad)], array_slice($tokens, 0, 12))));
    }
}
