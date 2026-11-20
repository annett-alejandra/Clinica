<?php

namespace App\Services\Paquetes\P6_ConsultasHistoriales;

use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnalisisClinicosService
{
    /**
     * Genera el catálogo de análisis clínicos y estudios diagnósticos realizados por el paciente,
     * personalizados según sus datos médicos base y sus citas/especialidades registradas.
     *
     * @param Paciente $paciente
     * @param Collection $citas
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorPaciente(Paciente $paciente, Collection $citas): array
    {
        $id = (int) $paciente->id_paciente;
        $esMujer = in_array(mb_strtoupper(substr((string) $paciente->genero, 0, 1)), ['F'], true);
        $edad = $paciente->fecha_nacimiento ? Carbon::parse($paciente->fecha_nacimiento)->age : 35;
        $infoBase = mb_strtolower((string) ($paciente->informacion_medica_basica ?? ''));
        $alergias = mb_strtolower((string) ($paciente->alergias ?? ''));

        $citasAtendidas = $citas->where('id_estado_cita', 3)->values();
        $especialidadesPaciente = $citas
            ->map(fn ($c) => mb_strtolower((string) ($c->especialidad?->nombre_especialidad ?? '')))
            ->filter()
            ->unique()
            ->values();

        $doctorPorEspecialidad = function (string $clave, string $fallback) use ($citas): string {
            $match = $citas->first(function ($c) use ($clave) {
                $esp = mb_strtolower((string) ($c->especialidad?->nombre_especialidad ?? ''));
                return str_contains($esp, $clave);
            });
            return $match?->medico?->nombre_completo ?? $fallback;
        };

        $fechaBase = function (int $indice, int $diasAtrasDefault) use ($citasAtendidas): string {
            if (isset($citasAtendidas[$indice]) && $citasAtendidas[$indice]->fecha_cita) {
                return Carbon::parse($citasAtendidas[$indice]->fecha_cita)->format('d/m/Y');
            }
            return Carbon::now()->subDays($diasAtrasDefault)->format('d/m/Y');
        };

        // Variación determinista sutil por paciente para valores realistas y coherentes
        $seed = ($id * 7) % 10;
        $analisis = [];

        // 1. Hemograma Completo Automatizado (estudio base presente en todos los expedientes)
        $hb = $esMujer ? round(12.4 + ($seed * 0.22), 1) : round(14.1 + ($seed * 0.24), 1);
        $hto = round($hb * 3.02, 1);
        $leuco = 5400 + ($seed * 310);
        $plaq = 215000 + ($seed * 11500);
        $vsg = 7 + ($seed % 6);

        $analisis[] = [
            'codigo' => sprintf('LAB-%s-%04dA', date('Y'), $id * 10 + 1),
            'titulo' => 'Hemograma Completo Automatizado',
            'categoria' => 'Laboratorio Clínico',
            'fecha' => $fechaBase(0, 12 + ($id % 10)),
            'medico_solicitante' => $doctorPorEspecialidad('medicina interna', 'Dr. Javier Gonzales Soria'),
            'laboratorio' => 'Laboratorio Central Automatizado · Clínica Vida Salud',
            'estado_general' => 'Normal',
            'tipo_badge' => 'normal',
            'resumen' => 'Serie roja, blanca y plaquetaria dentro de parámetros fisiológicos normales.',
            'observacion_medica' => 'Recuento hematológico equilibrado sin signos de anemia, leucocitosis ni alteraciones de coagulación primaria. Apto para control rutinario.',
            'parametros' => [
                ['nombre' => 'Hemoglobina (Hb)', 'valor' => number_format($hb, 1), 'unidad' => 'g/dL', 'referencia' => $esMujer ? '12.0 - 16.0' : '13.5 - 17.5', 'estado' => 'normal'],
                ['nombre' => 'Hematocrito (Hto)', 'valor' => number_format($hto, 1), 'unidad' => '%', 'referencia' => $esMujer ? '36.0 - 46.0' : '41.0 - 52.0', 'estado' => 'normal'],
                ['nombre' => 'Leucocitos totales', 'valor' => number_format($leuco, 0, '.', ','), 'unidad' => '/mm³', 'referencia' => '4,500 - 10,500', 'estado' => 'normal'],
                ['nombre' => 'Plaquetas', 'valor' => number_format($plaq, 0, '.', ','), 'unidad' => '/mm³', 'referencia' => '150,000 - 400,000', 'estado' => 'normal'],
                ['nombre' => 'VSG (1ra hora)', 'valor' => (string) $vsg, 'unidad' => 'mm/h', 'referencia' => $esMujer ? '0 - 20' : '0 - 15', 'estado' => 'normal'],
            ],
        ];

        // 2. Perfil Bioquímico y Lipídico (especialmente útil para control metabólico y cardiología)
        $glucosa = 84 + ($seed * 2) + ($edad > 50 ? 11 : 0);
        $colesterol = 168 + ($seed * 5) + (str_contains($infoBase, 'hipertens') || str_contains($infoBase, 'cardio') ? 22 : 0);
        $ldl = (int) round($colesterol * 0.57);
        $hdl = $esMujer ? 56 - ($seed % 7) : 48 - ($seed % 6);
        $trigliceridos = 105 + ($seed * 8);
        $creatinina = $esMujer ? round(0.72 + ($seed * 0.02), 2) : round(0.88 + ($seed * 0.03), 2);
        $tieneAtencionLipidos = $colesterol > 200 || $ldl > 130 || $glucosa >= 100;

        $analisis[] = [
            'codigo' => sprintf('BIO-%s-%04dB', date('Y'), $id * 10 + 2),
            'titulo' => 'Perfil Bioquímico, Glucemia y Lipídico',
            'categoria' => 'Química Sanguínea',
            'fecha' => $fechaBase(1, 28 + ($id % 15)),
            'medico_solicitante' => $doctorPorEspecialidad('cardiolog', 'Dr. Roberto Valenzuela M.'),
            'laboratorio' => 'Laboratorio Central Automatizado · Clínica Vida Salud',
            'estado_general' => $tieneAtencionLipidos ? 'Con observaciones' : 'Normal',
            'tipo_badge' => $tieneAtencionLipidos ? 'atencion' : 'normal',
            'resumen' => $tieneAtencionLipidos
                ? 'Leve elevación en perfil lipídico/glucémico; se sugiere control dietético y seguimiento.'
                : 'Glucemia en ayunas, función renal y perfil lipídico en rango óptimo.',
            'observacion_medica' => $tieneAtencionLipidos
                ? 'Se recomienda reforzar plan nutricional bajo en grasas saturadas, actividad física aeróbica regular y repetir perfil lipídico en 90 días.'
                : 'Perfil metabólico y función renal preservados. Continuar hábitos saludables y control preventivo anual.',
            'parametros' => [
                ['nombre' => 'Glucosa en ayunas', 'valor' => (string) $glucosa, 'unidad' => 'mg/dL', 'referencia' => '70 - 99', 'estado' => $glucosa >= 100 ? 'atencion' : 'normal'],
                ['nombre' => 'Colesterol Total', 'valor' => (string) $colesterol, 'unidad' => 'mg/dL', 'referencia' => '< 200', 'estado' => $colesterol > 200 ? 'atencion' : 'normal'],
                ['nombre' => 'Colesterol LDL', 'valor' => (string) $ldl, 'unidad' => 'mg/dL', 'referencia' => '< 130', 'estado' => $ldl > 130 ? 'atencion' : 'normal'],
                ['nombre' => 'Colesterol HDL', 'valor' => (string) $hdl, 'unidad' => 'mg/dL', 'referencia' => $esMujer ? '> 50' : '> 40', 'estado' => 'normal'],
                ['nombre' => 'Triglicéridos', 'valor' => (string) $trigliceridos, 'unidad' => 'mg/dL', 'referencia' => '< 150', 'estado' => $trigliceridos > 150 ? 'atencion' : 'normal'],
                ['nombre' => 'Creatinina Sérica', 'valor' => number_format($creatinina, 2), 'unidad' => 'mg/dL', 'referencia' => $esMujer ? '0.60 - 1.10' : '0.70 - 1.30', 'estado' => 'normal'],
            ],
        ];

        // 3. Estudio de especialidad personalizado según historial o perfil del paciente
        $tieneCardio = $especialidadesPaciente->contains(fn ($e) => str_contains($e, 'cardio')) || str_contains($infoBase, 'cardio') || str_contains($infoBase, 'hipertens') || $edad >= 45;
        $tieneGine = $esMujer && ($especialidadesPaciente->contains(fn ($e) => str_contains($e, 'gineco')) || $id % 2 === 0);
        $tieneTrauma = $especialidadesPaciente->contains(fn ($e) => str_contains($e, 'trauma')) || str_contains($infoBase, 'rodilla') || str_contains($infoBase, 'traumat');

        if ($tieneCardio) {
            $fc = 64 + ($seed % 14);
            $analisis[] = [
                'codigo' => sprintf('CAR-%s-%04dC', date('Y'), $id * 10 + 3),
                'titulo' => 'Electrocardiograma Digital (12 Derivaciones)',
                'categoria' => 'Cardiodiagnóstico',
                'fecha' => $fechaBase(2, 42 + ($id % 12)),
                'medico_solicitante' => $doctorPorEspecialidad('cardiolog', 'Dr. Roberto Valenzuela M.'),
                'laboratorio' => 'Unidad de Diagnóstico Cardiovascular · Clínica Vida Salud',
                'estado_general' => 'Normal',
                'tipo_badge' => 'normal',
                'resumen' => 'Ritmo sinusal regular, sin signos de isquemia aguda ni arritmias.',
                'observacion_medica' => 'Trazado electrocardiográfico con eje eléctrico normal, intervalos PR y QTc conservados. Sin alteraciones del segmento ST-T.',
                'parametros' => [
                    ['nombre' => 'Ritmo Cardíaco', 'valor' => 'Sinusal regular', 'unidad' => '—', 'referencia' => 'Sinusal', 'estado' => 'normal'],
                    ['nombre' => 'Frecuencia Cardíaca', 'valor' => (string) $fc, 'unidad' => 'lpm', 'referencia' => '60 - 100', 'estado' => 'normal'],
                    ['nombre' => 'Intervalo PR', 'valor' => '156', 'unidad' => 'ms', 'referencia' => '120 - 200', 'estado' => 'normal'],
                    ['nombre' => 'Complejo QRS', 'valor' => '88', 'unidad' => 'ms', 'referencia' => '70 - 110', 'estado' => 'normal'],
                    ['nombre' => 'Intervalo QTc', 'valor' => '412', 'unidad' => 'ms', 'referencia' => '< 450', 'estado' => 'normal'],
                ],
            ];
        }

        if ($tieneGine) {
            $tsh = round(1.45 + ($seed * 0.21), 2);
            $t4 = round(1.12 + ($seed * 0.04), 2);
            $analisis[] = [
                'codigo' => sprintf('END-%s-%04dD', date('Y'), $id * 10 + 4),
                'titulo' => 'Perfil Tiroideo y Vitamina D',
                'categoria' => 'Hormonas y Endocrinología',
                'fecha' => $fechaBase(2, 36 + ($id % 9)),
                'medico_solicitante' => $doctorPorEspecialidad('gineco', 'Dra. Claudia Morales Vaca'),
                'laboratorio' => 'Laboratorio de Inmunoquímica · Clínica Vida Salud',
                'estado_general' => $seed >= 7 ? 'Con observaciones' : 'Normal',
                'tipo_badge' => $seed >= 7 ? 'atencion' : 'normal',
                'resumen' => $seed >= 7
                    ? 'Función tiroidea normal; insuficiencia leve de Vitamina D.'
                    : 'Eje tiroideo y niveles de Vitamina D dentro de valores óptimos.',
                'observacion_medica' => $seed >= 7
                    ? 'TSH y T4 libre en rango eutiroideo. Considerar suplementación preventiva de Vitamina D3 y exposición solar matutina moderada.'
                    : 'Función endocrino-tiroidea sin alteraciones clínicas.',
                'parametros' => [
                    ['nombre' => 'TSH Ultrasensible', 'valor' => number_format($tsh, 2), 'unidad' => 'µUI/mL', 'referencia' => '0.40 - 4.20', 'estado' => 'normal'],
                    ['nombre' => 'T4 Libre', 'valor' => number_format($t4, 2), 'unidad' => 'ng/dL', 'referencia' => '0.80 - 1.80', 'estado' => 'normal'],
                    ['nombre' => '25-OH Vitamina D', 'valor' => $seed >= 7 ? '24.5' : '38.2', 'unidad' => 'ng/mL', 'referencia' => '30.0 - 80.0', 'estado' => $seed >= 7 ? 'atencion' : 'normal'],
                ],
            ];
        }

        if ($tieneTrauma) {
            $analisis[] = [
                'codigo' => sprintf('IMG-%s-%04dE', date('Y'), $id * 10 + 5),
                'titulo' => 'Radiografía Digital Articular y PCR Cuantitativa',
                'categoria' => 'Imagenología',
                'fecha' => $fechaBase(1, 21),
                'medico_solicitante' => $doctorPorEspecialidad('trauma', 'Dr. Marcos Castillo Antelo'),
                'laboratorio' => 'Centro de Imagenología Digital · Clínica Vida Salud',
                'estado_general' => 'Con observaciones',
                'tipo_badge' => 'atencion',
                'resumen' => 'Alineación ósea conservada sin fracturas; leve edema de partes blandas.',
                'observacion_medica' => 'Sin trazos de fractura ni luxación. Se sugiere continuar fisioterapia de fortalecimiento articular y reevaluación en consulta.',
                'parametros' => [
                    ['nombre' => 'Integridad cortical ósea', 'valor' => 'Conservada', 'unidad' => '—', 'referencia' => 'Sin fractura', 'estado' => 'normal'],
                    ['nombre' => 'Espacio articular', 'valor' => 'Mantenido', 'unidad' => '—', 'referencia' => 'Simétrico', 'estado' => 'normal'],
                    ['nombre' => 'Proteína C Reactiva (PCR)', 'valor' => '6.8', 'unidad' => 'mg/L', 'referencia' => '< 5.0', 'estado' => 'atencion'],
                ],
            ];
        }

        // 4. Examen General de Orina (EGO) + Estudio de imagen preventivo / alergias
        $analisis[] = [
            'codigo' => sprintf('URO-%s-%04dF', date('Y'), $id * 10 + 6),
            'titulo' => 'Examen General de Orina (EGO)',
            'categoria' => 'Uroanálisis',
            'fecha' => $fechaBase(3, 55 + ($id % 14)),
            'medico_solicitante' => $doctorPorEspecialidad('medicina', 'Dr. Javier Gonzales Soria'),
            'laboratorio' => 'Laboratorio Central Automatizado · Clínica Vida Salud',
            'estado_general' => 'Normal',
            'tipo_badge' => 'normal',
            'resumen' => 'Examen físico, químico y sedimento urinario sin hallazgos patológicos.',
            'observacion_medica' => 'Ausencia de nitritos, glucosa, proteínas y hematuria. Sin evidencia de infección del tracto urinario.',
            'parametros' => [
                ['nombre' => 'Densidad urinaria', 'valor' => '1.018', 'unidad' => 'g/mL', 'referencia' => '1.005 - 1.030', 'estado' => 'normal'],
                ['nombre' => 'pH urinario', 'valor' => '6.0', 'unidad' => 'pH', 'referencia' => '5.0 - 7.5', 'estado' => 'normal'],
                ['nombre' => 'Proteínas / Glucosa', 'valor' => 'Negativo', 'unidad' => '—', 'referencia' => 'Negativo', 'estado' => 'normal'],
                ['nombre' => 'Leucocitos en sedimento', 'valor' => '1 - 2', 'unidad' => '/campo', 'referencia' => '0 - 5', 'estado' => 'normal'],
                ['nombre' => 'Bacterias / Nitritos', 'valor' => 'Escasas / Neg.', 'unidad' => '—', 'referencia' => 'Negativo', 'estado' => 'normal'],
            ],
        ];

        // 5. Panel Inmunológico si tiene alergias registradas (ej. Annett Cruz, María Elena, Carmen Rosa, Andrea)
        if ($alergias !== '' && !str_contains($alergias, 'ninguna')) {
            $analisis[] = [
                'codigo' => sprintf('INM-%s-%04dG', date('Y'), $id * 10 + 7),
                'titulo' => 'Perfil Inmunológico y Sensibilidad Alérgica (IgE)',
                'categoria' => 'Inmunología',
                'fecha' => $fechaBase(2, 65),
                'medico_solicitante' => $doctorPorEspecialidad('dermatolog', 'Dr. Andrés Aguilera Peña'),
                'laboratorio' => 'Laboratorio de Inmunología Clínica · Clínica Vida Salud',
                'estado_general' => 'Con observaciones',
                'tipo_badge' => 'atencion',
                'resumen' => 'IgE total discretamente elevada concordante con antecedente alérgico documentado.',
                'observacion_medica' => 'Evitar exposición a los alérgenos declarados en ficha clínica (' . $paciente->alergias . '). Mantener registro visible en toda prescripción médica.',
                'parametros' => [
                    ['nombre' => 'Inmunoglobulina E Total (IgE)', 'valor' => (string) (145 + ($seed * 9)), 'unidad' => 'UI/mL', 'referencia' => '< 100', 'estado' => 'atencion'],
                    ['nombre' => 'Eosinófilos absolutos', 'valor' => '380', 'unidad' => '/mm³', 'referencia' => '0 - 450', 'estado' => 'normal'],
                    ['nombre' => 'PCR Ultrasensible', 'valor' => '1.4', 'unidad' => 'mg/L', 'referencia' => '< 3.0', 'estado' => 'normal'],
                ],
            ];
        }

        return $analisis;
    }
}
