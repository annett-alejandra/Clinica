<?php

namespace Tests\Unit;

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
}
