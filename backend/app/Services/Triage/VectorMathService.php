<?php

namespace App\Services\Triage;

/**
 * Motor matemático de procesamiento de lenguaje natural (NLP) y similitud vectorial.
 * Soporta tanto vectores densos (OpenAI / Gemini / Mistral) como vectores dispersos
 * TF-IDF enriquecidos con sinonimia clínica almacenados en columnas JSONB de PostgreSQL.
 */
class VectorMathService
{
    private const STOPWORDS = [
        'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'del', 'al', 'a', 'ante',
        'bajo', 'con', 'contra', 'desde', 'en', 'entre', 'hacia', 'hasta', 'para', 'por', 'segun',
        'sin', 'sobre', 'tras', 'y', 'e', 'o', 'u', 'ni', 'que', 'pero', 'mas', 'sino', 'porque',
        'como', 'cuando', 'donde', 'quien', 'cual', 'cuyo', 'mi', 'mis', 'tu', 'tus', 'su', 'sus',
        'me', 'te', 'se', 'nos', 'le', 'les', 'lo', 'yo', 'tu', 'el', 'ella', 'nosotros', 'ellos',
        'esto', 'esta', 'estos', 'estas', 'eso', 'esa', 'esos', 'esas', 'aqui', 'alli', 'muy',
        'mucho', 'mucha', 'poco', 'poca', 'todo', 'toda', 'todos', 'todas', 'tengo', 'siento',
        'estoy', 'esta', 'estan', 'hace', 'desde', 'ayer', 'hoy', 'dias', 'dia', 'semana', 'vez',
        'veces', 'siempre', 'nunca', 'tambien', 'solo', 'mas', 'menos', 'algo', 'nada', 'hay',
        'fue', 'ser', 'estar', 'tener', 'poder', 'hacer', 'parece', 'creo', 'hola', 'buenos',
        'buenas', 'doctor', 'doctora', 'porfavor', 'favor', 'gracias', 'ayuda', 'necesito', 'cita',
    ];

    /**
     * Normaliza texto en español: minúsculas, remoción de tildes y caracteres no alfanuméricos.
     */
    public function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');

        $reemplazos = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ];

        $texto = strtr($texto, $reemplazos);
        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    /**
     * Extrae tokens normalizados, raíces clínicas ligeras, bigramas y expansiones semánticas.
     *
     * @return array<string, float> Mapa término => peso relativo antes de normalizar L2
     */
    public function construirVectorLocal(string $texto, array $etiquetas = []): array
    {
        $normalizado = $this->normalizarTexto($texto);
        $palabras = $this->extraerPalabrasClave($normalizado);
        $sinonimos = config('triage.sinonimos_clinicos', []);

        $pesos = [];

        // 1. Unigramas y stemming ligero
        foreach ($palabras as $palabra) {
            $pesos[$palabra] = ($pesos[$palabra] ?? 0.0) + 1.0;

            $raiz = $this->raizLigera($palabra);
            if ($raiz !== $palabra && strlen($raiz) >= 4) {
                $pesos['stem:'.$raiz] = ($pesos['stem:'.$raiz] ?? 0.0) + 0.65;
            }

            // Expansión de sinonimia clínica en español
            if (isset($sinonimos[$palabra])) {
                foreach ($sinonimos[$palabra] as $sinonimo) {
                    $sinNorm = $this->normalizarTexto($sinonimo);
                    $claveSin = str_contains($sinNorm, ' ') ? 'bg:'.str_replace(' ', '_', $sinNorm) : $sinNorm;
                    $pesos[$claveSin] = ($pesos[$claveSin] ?? 0.0) + 0.72;
                }
            }
        }

        // 2. Bigramas contextuales (capturan "dolor pecho", "falta aire", "vision borrosa", etc.)
        $total = count($palabras);
        for ($i = 0; $i < $total - 1; $i++) {
            $bigrama = 'bg:'.$palabras[$i].'_'.$palabras[$i + 1];
            $pesos[$bigrama] = ($pesos[$bigrama] ?? 0.0) + 1.45;
        }

        // 3. Etiquetas clínicas estructuradas (mayor relevancia semántica para perfiles médicos)
        foreach ($etiquetas as $etiqueta) {
            $etNorm = $this->normalizarTexto((string) $etiqueta);
            if ($etNorm === '') {
                continue;
            }
            $tokensEt = $this->extraerPalabrasClave($etNorm);
            foreach ($tokensEt as $tok) {
                $pesos[$tok] = ($pesos[$tok] ?? 0.0) + 1.35;
                $raiz = $this->raizLigera($tok);
                if ($raiz !== $tok && strlen($raiz) >= 4) {
                    $pesos['stem:'.$raiz] = ($pesos['stem:'.$raiz] ?? 0.0) + 0.80;
                }
            }
            if (count($tokensEt) >= 2) {
                $pesos['bg:'.implode('_', array_slice($tokensEt, 0, 2))] = ($pesos['bg:'.implode('_', array_slice($tokensEt, 0, 2))] ?? 0.0) + 2.10;
            }
        }

        // Ponderación sublineal tipo TF (1 + log(tf)) + normalización L2
        foreach ($pesos as $clave => $valor) {
            $pesos[$clave] = 1.0 + log(max($valor, 1.0));
        }

        return $this->normalizarL2($pesos);
    }

    /**
     * Calcula la similitud coseno entre dos vectores (densos o dispersos).
     * Devuelve un valor en el rango [0.0, 1.0].
     */
    public function similitudCoseno(array $vectorA, array $vectorB): float
    {
        if ($vectorA === [] || $vectorB === []) {
            return 0.0;
        }

        // Caso 1: Vectores densos (listas indexadas de floats provenientes de OpenAI / Gemini / Mistral)
        if (array_is_list($vectorA) && array_is_list($vectorB)) {
            $len = min(count($vectorA), count($vectorB));
            $dot = 0.0;
            $normA = 0.0;
            $normB = 0.0;

            for ($i = 0; $i < $len; $i++) {
                $a = (float) $vectorA[$i];
                $b = (float) $vectorB[$i];
                $dot += $a * $b;
                $normA += $a * $a;
                $normB += $b * $b;
            }

            if ($normA <= 0.0 || $normB <= 0.0) {
                return 0.0;
            }

            $cos = $dot / (sqrt($normA) * sqrt($normB));

            return max(0.0, min(1.0, $cos));
        }

        // Caso 2: Vectores dispersos (mapas término => peso normalizado L2)
        if (! array_is_list($vectorA) && ! array_is_list($vectorB)) {
            $dot = 0.0;
            $normA = 0.0;
            $normB = 0.0;

            foreach ($vectorA as $k => $a) {
                $af = (float) $a;
                $normA += $af * $af;
                if (isset($vectorB[$k])) {
                    $dot += $af * (float) $vectorB[$k];
                }
            }

            foreach ($vectorB as $b) {
                $bf = (float) $b;
                $normB += $bf * $bf;
            }

            if ($normA <= 0.0 || $normB <= 0.0) {
                return 0.0;
            }

            return max(0.0, min(1.0, $dot / (sqrt($normA) * sqrt($normB))));
        }

        return 0.0;
    }

    /**
     * Normaliza un vector (denso o disperso) a norma unitaria L2.
     */
    public function normalizarL2(array $vector): array
    {
        $sumSq = 0.0;
        foreach ($vector as $val) {
            $f = (float) $val;
            $sumSq += $f * $f;
        }

        if ($sumSq <= 0.0) {
            return $vector;
        }

        $norm = sqrt($sumSq);
        $normalizado = [];
        foreach ($vector as $k => $val) {
            $normalizado[$k] = round(((float) $val) / $norm, 6);
        }

        return $normalizado;
    }

    /**
     * @return list<string>
     */
    public function extraerPalabrasClave(string $textoNormalizado): array
    {
        $partes = explode(' ', $textoNormalizado);
        $resultado = [];

        foreach ($partes as $parte) {
            $parte = trim($parte);
            if (strlen($parte) < 3) {
                continue;
            }
            if (in_array($parte, self::STOPWORDS, true)) {
                continue;
            }
            $resultado[] = $parte;
        }

        return $resultado;
    }

    /**
     * Stemmer ligero en español para agrupar plurales y sufijos clínicos frecuentes.
     */
    private function raizLigera(string $palabra): string
    {
        if (strlen($palabra) <= 4) {
            return $palabra;
        }

        $sufijos = ['aciones', 'iciones', 'amiento', 'imiento', 'idades', 'logia', 'icos', 'icas', 'osas', 'osos', 'ando', 'endo', 'cion', 'sion', 'dad', 'ico', 'ica', 'osa', 'oso', 'es', 's'];

        foreach ($sufijos as $sufijo) {
            if (str_ends_with($palabra, $sufijo) && (strlen($palabra) - strlen($sufijo)) >= 4) {
                return substr($palabra, 0, -strlen($sufijo));
            }
        }

        return $palabra;
    }
}
