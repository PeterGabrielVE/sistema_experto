<?php

namespace Tests\Feature;

use App\Models\ClinicalMeasurement;
use App\Models\LabResult;
use App\Services\InferenceEngine;
use Tests\TestCase;

/**
 * Clinical cut-offs come from shared/clinical_thresholds.json, the file the Python services read too.
 */
class ClinicalThresholdsTest extends TestCase
{
    public function test_config_is_the_shared_file(): void
    {
        $shared = json_decode(file_get_contents(base_path('shared/clinical_thresholds.json')), true);

        $this->assertSame($shared, config('clinical'));
        $this->assertSame(2.5, config('clinical.insulin_resistance.homa_ir'));
    }

    public function test_models_use_the_configured_cut_offs(): void
    {
        $result = new LabResult(['fasting_glucose' => 105, 'fasting_insulin' => 18.2]); // HOMA-IR 4,72
        $this->assertTrue($result->insulinResistanceIndicators()['HOMA-IR']['high']);

        config([
            'clinical.insulin_resistance.homa_ir' => 5.0,
            'clinical.bmi.obesity_from' => 35,
            'clinical.blood_pressure.stage2.systolic' => 150,
        ]);

        $this->assertFalse($result->insulinResistanceIndicators()['HOMA-IR']['high']);
        $this->assertSame(3, InferenceEngine::ruleForImc(32));
        $this->assertSame('Hipertensión etapa 1', (new ClinicalMeasurement(['systolic_bp' => 145, 'diastolic_bp' => 85]))->bloodPressureCategory());
    }
}
