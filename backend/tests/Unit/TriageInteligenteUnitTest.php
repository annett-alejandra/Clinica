<?php

namespace Tests\Unit;

use App\Models\Paquetes\P4_GestionCitas\ConsultaTriage;
use App\Services\Triage\EmergencyDetectorService;
use App\Services\Triage\VectorMathService;
use Tests\TestCase;

class TriageInteligenteUnitTest extends TestCase
{
    public function test_dense_and_sparse_cosine_similarity_behave_as_expected(): void
    {
        $math = new VectorMathService;

        $this->assertEqualsWithDelta(1.0, $math->similitudCoseno([1.0, 0.0, 0.0], [1.0, 0.0, 0.0]), 0.0001);
        $this->assertEqualsWithDelta(0.0, $math->similitudCoseno([1.0, 0.0], [0.0, 1.0]), 0.0001);

        $vecConsulta = $math->construirVectorLocal('Siento un dolor punzante en el pecho al respirar hondo y mareos leves desde ayer');
        $vecCardio = $math->construirVectorLocal(
            config('triage.catalogo_especialidades.cardiologia.perfil_base'),
            config('triage.catalogo_especialidades.cardiologia.condiciones')
        );
        $vecDerma = $math->construirVectorLocal(
            config('triage.catalogo_especialidades.dermatologia.perfil_base'),
            config('triage.catalogo_especialidades.dermatologia.condiciones')
        );

        $simCardio = $math->similitudCoseno($vecConsulta, $vecCardio);
        $simDerma = $math->similitudCoseno($vecConsulta, $vecDerma);

        $this->assertGreaterThan($simDerma, $simCardio);
        $this->assertGreaterThan(0.15, $simCardio);
    }

    public function test_emergency_detector_triggers_red_alert_on_critical_cardiopulmonary_symptoms(): void
    {
        $detector = new EmergencyDetectorService(new VectorMathService);

        $eval = $detector->evaluar('Siento un dolor punzante en el pecho al respirar hondo y mareos leves desde ayer');

        $this->assertTrue($eval['es_emergencia']);
        $this->assertSame('EMERGENCIA', $eval['nivel_urgencia']);
        $this->assertNotEmpty($eval['palabras_detectadas']);
        $this->assertSame('CARDIOPULMONAR', $eval['protocolo']['tipo']);
    }

    public function test_emergency_detector_distinguishes_normal_and_priority_cases(): void
    {
        $detector = new EmergencyDetectorService(new VectorMathService);

        $normal = $detector->evaluar('Me salieron ronchas y picazón leve en el brazo desde ayer');
        $this->assertFalse($normal['es_emergencia']);
        $this->assertSame('NORMAL', $normal['nivel_urgencia']);
        $this->assertNull($normal['protocolo']);

        $prioritario = $detector->evaluar('Tengo fiebre alta y migraña fuerte hace varios días');
        $this->assertFalse($prioritario['es_emergencia']);
        $this->assertSame('PRIORITARIO', $prioritario['nivel_urgencia']);
        $this->assertNull($prioritario['protocolo']);
    }

    public function test_consulta_triage_manual_resolution_catalog_and_seeded_red_alerts(): void
    {
        $this->assertCount(5, ConsultaTriage::ACCIONES_DERIVACION);
        $this->assertArrayHasKey('DERIVADO_URGENCIAS', ConsultaTriage::ACCIONES_DERIVACION);
        $this->assertArrayHasKey('AMBULANCIA_DESPACHADA', ConsultaTriage::ACCIONES_DERIVACION);
        $this->assertArrayHasKey('CITA_PRIORITARIA', ConsultaTriage::ACCIONES_DERIVACION);

        $sinResolucion = new ConsultaTriage;
        $sinResolucion->setRawAttributes(['recomendaciones_json' => json_encode([['porcentaje_afinidad' => 95]])]);
        $this->assertNull($sinResolucion->resolucionManual());

        $conResolucion = new ConsultaTriage;
        $conResolucion->setRawAttributes([
            'recomendaciones_json' => json_encode([
                'candidatos' => [],
                'resolucion_manual' => [
                    'tipo_derivacion' => 'DERIVADO_URGENCIAS',
                    'etiqueta_derivacion' => 'Derivado a Urgencias 24/7 (Box / Shock)',
                    'observacion' => 'Ingreso inmediato a Box 02',
                ],
            ]),
        ]);
        $res = $conResolucion->resolucionManual();
        $this->assertIsArray($res);
        $this->assertSame('DERIVADO_URGENCIAS', $res['tipo_derivacion']);

        $detector = new EmergencyDetectorService(new VectorMathService);
        $casosPrueba = [
            'Estoy embarazada de 7 meses y presento sangrado abundante con dolor abdominal fuerte desde hace 30 minutos',
            'Tengo reacción alérgica severa con hinchazón de garganta, lengua hinchada y me cuesta mucho respirar tras tomar un medicamento',
            'Sufrió golpe fuerte en la cabeza por caída de escaleras, perdió el conocimiento brevemente y presenta fractura expuesta en el antebrazo',
        ];
        foreach ($casosPrueba as $texto) {
            $eval = $detector->evaluar($texto);
            $this->assertTrue($eval['es_emergencia']);
            $this->assertSame('EMERGENCIA', $eval['nivel_urgencia']);
        }
    }
}
