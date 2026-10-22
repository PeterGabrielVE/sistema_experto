<?php

namespace Tests\Feature;

use App\Models\ClinicalRecord;
use App\Models\Diagnosis;
use App\Models\Food;
use App\Models\MealPlan;
use App\Models\Patient;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\PatientPlanDocument;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * The plan for the patient, as a printable document (US-5.2). The content is tested on the HTML
 * that DomPDF converts.
 */
class PatientPlanPdfTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private Diagnosis $diagnosis;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.clinic_timezone' => 'America/Santiago',
            'clinic.name' => 'Centro El Sauce',
            'clinic.address' => 'Av. Los Aromos 55, Concepción',
            'clinic.phone' => null,
            'clinic.email' => 'contacto@elsauce.cl',
            'clinic.logo_path' => public_path('assets/img/sauce-logo.png'),
        ]);
        $this->seed(RulesSeeder::class);

        $this->patient = Patient::factory()->create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1', 'address' => 'Pasaje Los Olmos 742',
        ]);
        ClinicalRecord::create([
            'patient_id' => $this->patient->id, 'consultation_reason' => 'Prediabetes y acantosis',
            'food_allergies' => 'Alergia al maní', 'medications' => 'Metformina 850 mg',
        ]);
        $this->patient->labResults()->create(['taken_at' => '2026-10-01', 'fasting_glucose' => 112.5, 'fasting_insulin' => 18.4]);

        $this->diagnosis = Diagnosis::create([
            'id_patient' => $this->patient->id, 'age' => 41, 'weight' => 83.4, 'size' => 162, 'imc' => 31.78,
            'physical_activity' => 1, 'id_rule' => 4, 'result_pulgar' => 1850, 'carbohydrate' => 200, 'protein' => 90, 'lipido' => 60,
            'created_by' => User::factory()->create(['name' => 'Dra. Carolina Muñoz'])->id,
        ]);
        $this->diagnosis->forceFill(['created_at' => '2026-10-21 13:00:00'])->save();
        Recommendation::create(['id_rule' => 4, 'description' => 'Prefiera cereales integrales y legumbres.']);
    }

    /**
     * A saved plan as MealPlanService::rebuild() stores it.
     */
    private function savedPlan(int $days = 2, string $breakfast = 'Avena'): MealPlan
    {
        $day = fn (int $n) => [
            'day' => $n,
            'meals' => [
                ['key' => 'desayuno', 'label' => 'Desayuno', 'energy_target' => 370, 'items' => [
                    ['food_id' => 1, 'name' => $breakfast, 'group' => 'Cereales', 'portions' => 1.5, 'grams' => 75, 'energy' => 210, 'glycemic_load' => 21.7],
                ], 'totals' => ['energy' => 210]],
                ['key' => 'almuerzo', 'label' => 'Almuerzo', 'energy_target' => 555, 'items' => [
                    ['food_id' => 2, 'name' => 'Aceite de Oliva', 'group' => 'Aceites', 'portions' => 0.5, 'grams' => null, 'energy' => 90],
                ], 'totals' => ['energy' => 90]],
            ],
            'totals' => ['energy' => 1792.4, 'carbohydrates' => 197.5, 'proteins' => 88, 'fats' => 68, 'glycemic_load' => 97.3, 'saturated_fat' => 13.1],
            'deviation_percent' => ['energy' => -3.1],
            'notes' => ['Supera el máximo de la carga glucémica.'],
        ];

        return MealPlan::create([
            'diagnosis_id' => $this->diagnosis->id, 'edited' => true,
            'plan' => ['status' => 'editado', 'targets' => ['energy' => 1850], 'days' => array_map($day, range(1, $days)), 'notes' => []],
        ]);
    }

    private function html(): string
    {
        $data = app(PatientPlanDocument::class)->data($this->diagnosis->fresh());

        return view('meal_plans.pdf', $data)->render();
    }

    #[Group('US-5.2/AC-1')]
    public function test_document_has_the_clinic_patient_professional_and_menu(): void
    {
        $this->savedPlan(1);

        $html = $this->html();

        $this->assertStringContainsString('src="data:image/png;base64,', $html);
        foreach (['Centro El Sauce', 'Plan alimentario', 'Ana Rojas', '21/10/2026', 'Dra. Carolina Muñoz',
            'Desayuno', 'Avena', '1,5 porciones', '75 g', 'Almuerzo', 'Aceite de Oliva', '½ porción'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }

    #[Group('US-5.2/AC-2')]
    public function test_document_has_daily_energy_recommendations_advice_and_allergies(): void
    {
        $this->savedPlan(1);

        $html = $this->html();

        foreach ([
            'Energía 1.792 kcal', 'Carbohidratos 197,5 g', 'Proteínas 88 g', 'Grasas 68 g',
            'Prefiera cereales integrales y legumbres.',
            'caminar por lo menos 30 minutos al día', // general advice, the same text as the report
            'Alergias e intolerancias consideradas', 'Alergia al maní',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }

    #[Group('US-5.2/AC-2')]
    public function test_document_has_no_rut_address_bmi_category_nor_clinical_record(): void
    {
        $this->savedPlan(1);

        // The embedded logo is base64: random letters that could match by chance.
        $html = preg_replace('#data:image/[a-z]+;base64,[A-Za-z0-9+/=]+#', '', $this->html());

        foreach (['11111111-1', 'Pasaje Los Olmos', '31,78', '31.78', 'IMC', 'Obesidad', 'categoría', 'Carga glucémica', '97,3',
            '83,4', 'Prediabetes', 'acantosis', 'Metformina', '112', '18,4', 'Supera', 'desvío', '1.850'] as $absent) {
            $this->assertStringNotContainsStringIgnoringCase($absent, $html, "El plan para el paciente no debe incluir: {$absent}");
        }
    }

    #[Group('US-5.2/AC-2')]
    public function test_missing_allergies_and_recommendations_are_not_shown_as_none(): void
    {
        $this->savedPlan(1);
        $this->patient->clinicalRecord->update(['food_allergies' => null]);
        Recommendation::query()->delete();

        $html = $this->html();

        $this->assertStringContainsString('Sin alergias ni intolerancias registradas.', $html);
        $this->assertStringNotContainsString('Recomendaciones', $html);
    }

    #[Group('US-5.2/AC-4')]
    public function test_each_day_starts_on_a_new_page(): void
    {
        $this->savedPlan(3);

        $html = $this->html();

        $this->assertSame(2, substr_count($html, 'page-break-before: always'));
        $this->assertMatchesRegularExpression('/Día 1.*Día 2.*Día 3/s', $html);
    }

    #[Group('US-5.2/AC-5')]
    public function test_document_shows_the_plan_as_saved(): void
    {
        $food = Food::create([
            'name' => 'Avena', 'item' => 'Cereales', 'id_group' => 3, 'portion' => '1', 'gr' => '50',
            'kcal' => '140', 'protein' => '5', 'lipid' => '2', 'cho' => '25', 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0',
        ]);
        $this->savedPlan(1, 'Avena');
        // After saving: the food is renamed and retired, and the category's rules change.
        $food->forceFill(['name' => 'Avena tradicional', 'retired_at' => now()])->save();

        $html = $this->html();

        $this->assertStringContainsString('Avena', $html);
        $this->assertStringNotContainsString('Avena tradicional', $html);
        $this->assertStringContainsString('1,5 porciones', $html);
    }

    #[Group('US-5.2/AC-8')]
    public function test_without_logo_the_clinic_name_is_shown(): void
    {
        $this->savedPlan(1);
        config(['clinic.logo_path' => public_path('assets/img/no-existe.png')]);

        $html = $this->html();

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('Centro El Sauce', $html);
    }

    #[Group('US-5.2/AC-9')]
    public function test_footer_shows_only_the_configured_contact_data(): void
    {
        $this->savedPlan(1);

        $html = $this->html();

        $this->assertStringContainsString('Av. Los Aromos 55, Concepción', $html);
        $this->assertStringContainsString('contacto@elsauce.cl', $html);
        $this->assertStringNotContainsString('Teléfono', $html); // not configured

        config(['clinic.address' => null, 'clinic.email' => null, 'clinic.phone' => '+56 41 222 3344']);
        $html = $this->html();
        $this->assertStringContainsString('Teléfono +56 41 222 3344', $html);
        $this->assertStringNotContainsString('contacto@elsauce.cl', $html);
    }

    public function test_without_a_saved_plan_there_is_no_document(): void
    {
        $this->assertNull(app(PatientPlanDocument::class)->data($this->diagnosis));
    }
}
