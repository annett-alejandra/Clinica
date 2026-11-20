<?php

/*
|--------------------------------------------------------------------------
| Configuración del Motor de Triage Clínico Inteligente (NLP Vectorial + Scoring)
|--------------------------------------------------------------------------
| Soporta modo híbrido: API de Embeddings/LLM (OpenAI, Gemini o Mistral)
| con fallback automático y 100% funcional mediante vectorización local TF-IDF
| + ontología clínica en español almacenada en JSONB de PostgreSQL.
*/

return [

    // Proveedor activo: hereda IA_PROVIDER de la arquitectura IA si TRIAGE_AI_PROVIDER no está definido
    'provider' => env('TRIAGE_AI_PROVIDER', env('IA_PROVIDER', 'auto')),

    // Tiempo máximo de espera para llamadas HTTP a APIs externas (segundos)
    'http_timeout' => (int) env('TRIAGE_HTTP_TIMEOUT', env('IA_TIMEOUT', 12)),

    // Habilitar explicación redactada por LLM cuando haya API key configurada
    'use_llm_explanation' => filter_var(env('TRIAGE_USE_LLM_EXPLANATION', true), FILTER_VALIDATE_BOOL),

    'providers' => [
        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'chat_model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
            'chat_model' => env('GEMINI_CHAT_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash')),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'chat_model' => env('OPENAI_CHAT_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini')),
        ],
        'mistral' => [
            'api_key' => env('MISTRAL_API_KEY'),
            'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
            'embedding_model' => env('MISTRAL_EMBEDDING_MODEL', 'mistral-embed'),
            'chat_model' => env('MISTRAL_CHAT_MODEL', 'mistral-small-latest'),
        ],
    ],

    // Pesos del algoritmo de matching multicriterio (deben sumar 1.00)
    'scoring_weights' => [
        'vectorial' => 0.55,
        'criterios' => 0.25,
        'efectividad' => 0.12,
        'disponibilidad' => 0.08,
    ],

    // Números y protocolos institucionales de emergencia
    'emergencia' => [
        'telefono_urgencias' => env('TRIAGE_TELEFONO_URGENCIAS', '+591 3 300 0000'),
        'linea_ambulancia' => env('TRIAGE_LINEA_AMBULANCIA', '160 / 911'),
        'ubicacion_urgencias' => 'Ingreso Directo Urgencias 24/7 — Planta Baja, Complejo Hospitalario Clínica Vida Salud',
    ],

    // Sinónimos y equivalencias coloquiales -> conceptos clínicos normalizados (sin tildes, minúsculas)
    'sinonimos_clinicos' => [
        'pecho' => ['torax', 'toracico', 'cardiaco', 'corazon', 'precordial'],
        'corazon' => ['cardiaco', 'cardiologia', 'latidos', 'palpitaciones', 'pecho'],
        'latidos' => ['palpitaciones', 'taquicardia', 'arritmia', 'cardiaco'],
        'palpitaciones' => ['taquicardia', 'arritmia', 'corazon', 'cardiologia'],
        'presion' => ['hipertension', 'tension arterial', 'cardiovascular'],
        'ahogo' => ['disnea', 'falta de aire', 'respirar', 'respiratorio'],
        'respirar' => ['disnea', 'pulmonar', 'toracico', 'pecho'],
        'mareo' => ['mareos', 'vertigo', 'inestabilidad', 'sincope'],
        'mareos' => ['mareo', 'vertigo', 'neurologia', 'cardiologia'],
        'desmayo' => ['sincope', 'perdida conocimiento', 'emergencia'],
        'cabeza' => ['cefalea', 'migrana', 'neurologia', 'jaqueca'],
        'jaqueca' => ['migrana', 'cefalea', 'neurologia'],
        'migrana' => ['cefalea', 'dolor cabeza', 'neurologia', 'fotofobia'],
        'hormigueo' => ['parestesia', 'neuropatia', 'nervio', 'neurologia'],
        'convulsion' => ['epilepsia', 'convulsiones', 'neurologia', 'emergencia'],
        'hueso' => ['fractura', 'oseo', 'traumatologia', 'ortopedia'],
        'rodilla' => ['articulacion', 'menisco', 'ligamento', 'traumatologia'],
        'espalda' => ['columna', 'lumbar', 'lumbalgia', 'traumatologia'],
        'tobillo' => ['esguince', 'fractura', 'articulacion', 'traumatologia'],
        'hombro' => ['articulacion', 'tendinitis', 'manguito rotador', 'traumatologia'],
        'golpe' => ['trauma', 'traumatismo', 'contusion', 'traumatologia'],
        'caida' => ['traumatismo', 'fractura', 'esguince', 'traumatologia'],
        'piel' => ['dermatologia', 'cutaneo', 'dermatitis', 'erupcion'],
        'ronchas' => ['urticaria', 'alergia', 'erupcion', 'dermatologia', 'picazon'],
        'sarpullido' => ['erupcion', 'exantema', 'dermatitis', 'dermatologia', 'piel'],
        'picazon' => ['prurito', 'dermatitis', 'alergia', 'piel', 'dermatologia'],
        'granos' => ['acne', 'foliculitis', 'dermatologia', 'piel'],
        'lunar' => ['nevus', 'melanoma', 'dermatoscopia', 'dermatologia', 'piel'],
        'mancha' => ['lesion pigmentada', 'vitiligo', 'melasma', 'dermatologia', 'piel'],
        'nino' => ['pediatria', 'infantil', 'menor', 'hijo'],
        'nina' => ['pediatria', 'infantil', 'menor', 'hija'],
        'bebe' => ['pediatria', 'lactante', 'neonato', 'infantil'],
        'hijo' => ['pediatria', 'infantil', 'nino'],
        'hija' => ['pediatria', 'infantil', 'nina'],
        'embarazo' => ['prenatal', 'obstetricia', 'gestacion', 'ginecologia'],
        'menstruacion' => ['ciclo menstrual', 'ginecologia', 'dismenorrea', 'ovario'],
        'regla' => ['menstruacion', 'ginecologia', 'sangrado'],
        'ojo' => ['ocular', 'oftalmologia', 'vision', 'vista'],
        'ojos' => ['ocular', 'oftalmologia', 'vision', 'conjuntivitis'],
        'vista' => ['vision', 'oftalmologia', 'ocular', 'borrosa'],
        'borrosa' => ['vision borrosa', 'refraccion', 'retina', 'oftalmologia'],
        'fiebre' => ['febril', 'infeccion', 'temperatura', 'escalofrios', 'medicina interna'],
        'estomago' => ['abdominal', 'gastritis', 'digestion', 'medicina interna'],
        'cansancio' => ['fatiga', 'astenia', 'anemia', 'medicina interna'],
        'azucar' => ['diabetes', 'glucosa', 'metabolico', 'medicina interna'],
        'garganta' => ['faringitis', 'amigdalitis', 'infeccion respiratoria', 'medicina interna'],
        'tos' => ['bronquitis', 'respiratorio', 'flema', 'medicina interna'],
    ],

    // Reglas de detección de emergencia (Código Rojo) y prioridad alta (Código Amarillo)
    'reglas_emergencia' => [
        // Frases directas de emergencia (cualquier coincidencia dispara Alerta Roja)
        'frases_criticas' => [
            'dolor de pecho intenso',
            'dolor en el pecho intenso',
            'dolor opresivo en el pecho',
            'presion fuerte en el pecho',
            'dolor punzante en el pecho',
            'dolor de pecho al respirar',
            'dolor en el pecho al respirar',
            'falta de aire subita',
            'no puedo respirar',
            'me cuesta mucho respirar',
            'ahogo intenso',
            'asfixia',
            'perdida de conocimiento',
            'perdio el conocimiento',
            'se desmayo',
            'desmayo repentino',
            'convulsion',
            'convulsiones',
            'ataque epileptico',
            'hemorragia abundante',
            'sangrado abundante',
            'sangrado intenso',
            'vomito con sangre',
            'tosiendo sangre',
            'paralisis facial',
            'cara torcida',
            'no puede hablar',
            'dificultad para hablar',
            'brazo dormido repentino',
            'debilidad subita en un lado',
            'dolor abdominal insoportable',
            'reaccion alergica severa',
            'hinchazon de garganta',
            'lengua hinchada',
            'anafilaxia',
            'golpe fuerte en la cabeza',
            'traumatismo craneal',
            'fractura expuesta',
            'sangrado en el embarazo',
            'infarto',
            'paro cardiaco',
            'derrame cerebral',
            'acv',
            'intento de suicidio',
            'quiero quitarme la vida',
        ],

        // Combinaciones de síntomas que juntos constituyen bandera roja clínica
        'combinaciones_criticas' => [
            [
                'etiqueta' => 'Dolor torácico con dificultad respiratoria o mareos (riesgo cardiopulmonar)',
                'grupo_a' => ['dolor pecho', 'dolor en el pecho', 'dolor toracico', 'opresion pecho', 'pecho'],
                'grupo_b' => ['respirar', 'falta de aire', 'ahogo', 'mareo', 'mareos', 'brazo izquierdo', 'sudor frio', 'sudoracion', 'punzante', 'opresivo'],
                'protocolo' => 'CARDIOPULMONAR',
            ],
            [
                'etiqueta' => 'Déficit neurológico agudo (sospecha de evento cerebrovascular)',
                'grupo_a' => ['debilidad', 'adormecimiento', 'hormigueo', 'paralisis', 'confusion'],
                'grupo_b' => ['hablar', 'cara', 'boca torcida', 'vision doble', 'repentino', 'subito', 'un lado'],
                'protocolo' => 'NEUROLOGICO',
            ],
            [
                'etiqueta' => 'Fiebre alta con rigidez o compromiso del estado general',
                'grupo_a' => ['fiebre alta', 'fiebre 39', 'fiebre 40', 'mucha fiebre'],
                'grupo_b' => ['rigidez cuello', 'convulsion', 'confusion', 'manchas piel', 'no responde', 'bebe'],
                'protocolo' => 'INFECCIOSO_AGUDO',
            ],
            [
                'etiqueta' => 'Compromiso obstétrico urgente',
                'grupo_a' => ['embarazo', 'embarazada', 'gestacion'],
                'grupo_b' => ['sangrado', 'hemorragia', 'perdida de liquido', 'dolor fuerte', 'contracciones fuertes', 'no se mueve'],
                'protocolo' => 'OBSTETRICO',
            ],
        ],

        // Indicadores de atención prioritaria (Código Amarillo, no emergencia vital inmediata)
        'frases_prioritarias' => [
            'fiebre alta',
            'fiebre hace varios dias',
            'dolor fuerte',
            'dolor intenso',
            'inflamacion severa',
            'infeccion',
            'migrana fuerte',
            'vision borrosa repentina',
            'taquicardia',
            'palpitaciones frecuentes',
            'presion alta',
            'hipertension',
            'vomitos persistentes',
            'deshidratacion',
            'esguince grave',
            'sospecha de fractura',
        ],
    ],

    // Base de conocimientos médica estructurada por especialidad (usada para enriquecer perfiles y vectores)
    'catalogo_especialidades' => [
        'cardiologia' => [
            'subespecialidades' => ['Cardiología Clínica', 'Electrocardiografía y Arritmias', 'Prevención Cardiovascular e Hipertensión'],
            'condiciones' => [
                'dolor de pecho', 'dolor toracico', 'dolor punzante en el pecho', 'opresion precordial',
                'palpitaciones', 'taquicardia', 'arritmia', 'hipertension arterial', 'presion alta',
                'mareos', 'sincope', 'falta de aire al esfuerzo', 'disnea', 'edema de piernas',
                'soplo cardiaco', 'colesterol alto', 'chequeo cardiovascular', 'ergometria',
            ],
            'perfil_base' => 'Especialista en diagnóstico y tratamiento de enfermedades del corazón y sistema cardiovascular: dolor de pecho, dolor punzante torácico, opresión precordial, mareos, palpitaciones, taquicardia, arritmias, hipertensión arterial, dificultad para respirar al esfuerzo, control de colesterol y evaluación electrocardiográfica.',
        ],
        'pediatria' => [
            'subespecialidades' => ['Pediatría General', 'Control de Crecimiento y Desarrollo', 'Infectología Pediátrica'],
            'condiciones' => [
                'fiebre infantil', 'fiebre en nino', 'fiebre en bebe', 'tos infantil', 'resfrio en ninos',
                'vacunacion', 'control de nino sano', 'dolor de oido infantil', 'diarrea infantil',
                'vomitos en ninos', 'erupcion infantil', 'alergia pediatrica', 'bajo peso', 'lactancia',
                'bronquiolitis', 'amigdalitis infantil',
            ],
            'perfil_base' => 'Atención médica integral para recién nacidos, bebés, niños y adolescentes: fiebre infantil, infecciones respiratorias, tos con flema, dolor de oído, diarrea, vómitos, alergias, erupciones en la piel, esquema de vacunación y control de crecimiento y desarrollo.',
        ],
        'traumatologia' => [
            'subespecialidades' => ['Traumatología Deportiva', 'Cirugía Articular y Rodilla', 'Columna y Ortopedia'],
            'condiciones' => [
                'dolor de rodilla', 'lesion de menisco', 'ligamento', 'esguince de tobillo', 'fractura',
                'dolor lumbar', 'dolor de espalda', 'hernia discal', 'dolor de hombro', 'tendinitis',
                'contractura muscular', 'golpe articular', 'caida', 'luxacion', 'artrosis', 'desgarro muscular',
                'dolor de muneca', 'dolor de cadera', 'rehabilitacion postquirurgica',
            ],
            'perfil_base' => 'Especialista en lesiones del sistema musculoesquelético, huesos, articulaciones, ligamentos y tendones: dolor de rodilla, esguince de tobillo, fracturas, golpes, caídas, dolor de espalda y columna lumbar, dolor de hombro, lesiones deportivas, tendinitis, desgarros y artrosis.',
        ],
        'neurologia' => [
            'subespecialidades' => ['Cefaleas y Migrañas', 'Epilepsia y Trastornos Convulsivos', 'Neurología Vascular y Neuromuscular'],
            'condiciones' => [
                'dolor de cabeza', 'cefalea', 'migrana', 'jaqueca', 'vertigo', 'mareos frecuentes',
                'hormigueo en manos', 'adormecimiento', 'parestesia', 'epilepsia', 'convulsiones',
                'temblores', 'perdida de memoria', 'insomnio neurologico', 'neuropatia', 'neuralgia',
                'debilidad muscular', 'vision doble', 'zumbido de oidos',
            ],
            'perfil_base' => 'Diagnóstico y manejo de trastornos del cerebro, médula espinal y nervios periféricos: dolor de cabeza persistente, migrañas con aura o visión borrosa, vértigo, mareos recurrentes, hormigueo o adormecimiento en extremidades, temblores, convulsiones, epilepsia, pérdida de memoria y neuralgias.',
        ],
        'dermatologia' => [
            'subespecialidades' => ['Dermatología Clínica', 'Dermatoscopía y Lesiones Pigmentadas', 'Alergias Cutáneas'],
            'condiciones' => [
                'sarpullido', 'ronchas', 'urticaria', 'picazon en la piel', 'prurito', 'dermatitis',
                'eczema', 'acne', 'granos', 'manchas en la piel', 'lunares', 'caida de cabello',
                'alopecia', 'hongos en unas', 'micosis', 'psoriasis', 'alergia en la piel', 'quemadura solar',
                'resequedad cutanea', 'herpes',
            ],
            'perfil_base' => 'Especialista en enfermedades de la piel, cabello y uñas: sarpullido, ronchas, picazón intensa, dermatitis, alergias cutáneas, acné, manchas, revisión de lunares por dermatoscopía, psoriasis, hongos en piel o uñas y caída del cabello.',
        ],
        'ginecologia' => [
            'subespecialidades' => ['Ginecología Clínica', 'Obstetricia y Control Prenatal', 'Salud Reproductiva Femenina'],
            'condiciones' => [
                'control prenatal', 'embarazo', 'dolor pelvico', 'dolor menstrual', 'ciclo menstrual irregular',
                'retraso menstrual', 'sangrado vaginal', 'infeccion vaginal', 'flujo vaginal', 'ovario poliquistico',
                'miomas', 'menopausia', 'anticoncepcion', 'papanicolaou', 'dolor mamario',
            ],
            'perfil_base' => 'Cuidado integral de la salud femenina y embarazo: control prenatal, seguimiento obstétrico, dolor menstrual intenso, ciclos irregulares, dolor pélvico, infecciones o flujo vaginal, síndrome de ovario poliquístico, planificación familiar y menopausia.',
        ],
        'medicina interna' => [
            'subespecialidades' => ['Diagnóstico Clínico Integral', 'Enfermedades Crónicas y Metabólicas', 'Infectología de Adultos'],
            'condiciones' => [
                'fiebre', 'malestar general', 'cansancio extremo', 'fatiga cronica', 'diabetes', 'azucar alta',
                'dolor abdominal', 'gastritis', 'reflujo', 'acidez estomacal', 'tos persistente', 'dolor de garganta',
                'infeccion urinaria', 'anemia', 'tiroides', 'perdida de peso', 'chequeo general', 'control metabolico',
                'nauseas', 'diarrea', 'escalofrios',
            ],
            'perfil_base' => 'Evaluación clínica integral del adulto y manejo de enfermedades complejas o multisistémicas: fiebre, malestar general, fatiga crónica, diabetes, hipertensión, gastritis, dolor abdominal, infecciones respiratorias o urinarias, trastornos tiroideos, anemia y chequeos médicos preventivos.',
        ],
        'oftalmologia' => [
            'subespecialidades' => ['Oftalmología Clínica', 'Retina y Glaucoma', 'Cirugía Refractiva'],
            'condiciones' => [
                'vision borrosa', 'dolor ocular', 'ojo rojo', 'ojos secos', 'ardor en los ojos',
                'conjuntivitis', 'lagrimeo', 'sensibilidad a la luz', 'fotofobia', 'destellos de luz',
                'moscas volantes', 'cataratas', 'glaucoma', 'miopia', 'astigmatismo', 'orzuelo',
                'perdida de vision',
            ],
            'perfil_base' => 'Especialista en salud ocular y visual: visión borrosa, dolor en los ojos, ojo rojo, conjuntivitis, ardor, picazón ocular, lagrimeo, sensibilidad a la luz, destellos, cataratas, glaucoma, retinopatía y medición de lentes.',
        ],
    ],
];
