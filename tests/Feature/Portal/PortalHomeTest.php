<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\ClinicalRecord;
use App\Models\Diagnosis;
use App\Models\LabResult;
use App\Models\MealPlan;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Home of the patient portal: next appointment and saved meal plan, read only (US-6.3, D-3).
 */
class PortalHomeTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 12:00:00');
        config(['app.clinic_timezone' => 'America/Santiago']);
        $this->seed(RulesSeeder::class); // diagnoses.id_rule references rules.id

        $this->patient = Patient::factory()->create(['first_name' => 'Ana']);
        $this->account = User::factory()->patient($this->patient)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function consultation(Patient $patient, string $date = '2026-10-01 12:00:00'): Diagnosis
    {
        $diagnosis = Diagnosis::create([
            'id_patient' => $patient->id, 'age' => 41, 'weight' => 83.4, 'size' => 162, 'imc' => 31.78,
            'physical_activity' => 1, 'id_rule' => 4, 'result_pulgar' => 1850, 'carbohydrate' => 200, 'protein' => 90, 'lipido' => 60,
        ]);
        $diagnosis->forceFill(['created_at' => $date])->save();

        return $diagnosis;
    }

    /**
     * A saved menu (MealPlanService) with its targets, totals and notes, as stored.
     */
    private function savedPlan(Diagnosis $diagnosis, string $food = 'Avena', string $date = '2026-10-01 12:00:00'): MealPlan
    {
        $item = fn (string $name, float $portions, ?float $grams) => [
            'food_id' => 1, 'name' => $name, 'group' => 'Cereales', 'portions' => $portions, 'grams' => $grams,
            'energy' => 140 * $portions, 'carbohydrates' => 30, 'proteins' => 3, 'fats' => 1, 'glycemic_load' => 21.7, 'saturated_fat' => 0.2,
        ];
        $day = fn (int $n) => [
            'day' => $n,
            'meals' => [
                ['key' => 'desayuno', 'label' => 'Desayuno', 'energy_target' => 370, 'items' => [$item($food, 1.5, 75)], 'totals' => ['energy' => 210]],
                ['key' => 'almuerzo', 'label' => 'Almuerzo', 'energy_target' => 555, 'items' => [$item('Aceite de Oliva', 0.5, null)], 'totals' => ['energy' => 90]],
            ],
            'totals' => ['energy' => 1792.4, 'carbohydrates' => 197, 'proteins' => 88, 'fats' => 68, 'glycemic_load' => 97.3, 'saturated_fat' => 13.1],
            'deviation_percent' => ['energy' => -3.1, 'carbohydrates' => -1.5],
            'notes' => ['Supera el máximo de la carga glucémica.'],
        ];
        $plan = MealPlan::create([
            'diagnosis_id' => $diagnosis->id, 'edited' => false,
            'plan' => [
                'status' => 'generado',
                'targets' => ['energy' => 1850, 'carbohydrates' => 200, 'proteins' => 90, 'fats' => 60],
                'limits' => ['glycemic_load' => 100, 'saturated_fat' => 14],
                'days' => [$day(1), $day(2)],
                'notes' => ['Sin valores nutricionales, no se usan: Mote Crudo.'],
            ],
        ]);
        $plan->forceFill(['updated_at' => $date])->save();

        return $plan;
    }

    #[Group('US-6.3/AC-4')]
    public function test_portal_shows_name_next_appointment_and_saved_meal_plan(): void
    {
        Appointment::factory()->create([
            'patient_id' => $this->patient->id, 'scheduled_at' => '2026-11-04 13:30:00', 'notes' => 'Venir en ayuno de 8 horas.',
            'user_id' => User::factory()->create(['name' => 'Dra. Carolina Muñoz'])->id,
        ]);
        $this->savedPlan($this->consultation($this->patient, '2026-08-01 12:00:00'), 'Arroz', '2026-08-01 12:00:00');
        $this->savedPlan($this->consultation($this->patient), 'Avena');

        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertOk()
            ->assertSee('Hola Ana')
            ->assertSeeInOrder(['Próxima consulta', '04/11/2026 10:30', 'Dra. Carolina Muñoz', 'Venir en ayuno de 8 horas.'])
            ->assertSeeInOrder(['Su plan alimentario', 'guardado el 01/10/2026', 'Día 1', 'Desayuno', 'Avena', '1,5 porciones (75 g)', 'Almuerzo', 'Aceite de Oliva', '½ porción', 'Día 2'])
            ->assertDontSee('Arroz'); // only the latest saved plan
    }

    #[Group('US-6.3/AC-4')]
    public function test_portal_without_appointment_or_meal_plan_says_so(): void
    {
        // A cancelled appointment and a past one are not "next"; a consultation without a saved plan.
        Appointment::factory()->cancelled()->create(['patient_id' => $this->patient->id, 'scheduled_at' => '2026-11-04 13:30:00']);
        Appointment::factory()->create(['patient_id' => $this->patient->id, 'scheduled_at' => '2026-10-01 13:30:00']);
        $this->consultation($this->patient);

        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertOk()
            ->assertSee('No tiene una próxima consulta programada.')
            ->assertSee('Su profesional aún no le ha guardado un plan alimentario.');
    }

    #[Group('US-6.3/AC-4')]
    public function test_portal_shows_no_clinical_data(): void
    {
        $this->savedPlan($this->consultation($this->patient));
        ClinicalRecord::create(['patient_id' => $this->patient->id, 'consultation_reason' => 'Prediabetes y acantosis']);
        LabResult::create(['patient_id' => $this->patient->id, 'taken_at' => '2026-10-01', 'fasting_glucose' => 112.5, 'fasting_insulin' => 18.4]);

        $html = $this->actingAs($this->account)->get(route('portal.home'))->assertOk()->getContent();

        foreach (['31,78', '31.78', '83,4', '83.4', 'Obesidad', 'Prediabetes', 'acantosis', '112', '18,4',
            'IMC', 'HOMA', 'glucémica', 'Supera', 'Mote Crudo', '1850', '1.850', '1792', 'kcal', 'desvío'] as $clinical) {
            $this->assertStringNotContainsStringIgnoringCase($clinical, $html, "El portal no debe mostrar: {$clinical}");
        }
    }

    #[Group('US-6.3/AC-6')]
    public function test_patient_sees_only_their_own_data(): void
    {
        $other = Patient::factory()->create(['first_name' => 'Berta']);
        User::factory()->patient($other)->create();
        Appointment::factory()->create(['patient_id' => $other->id, 'scheduled_at' => '2026-11-20 15:00:00']);
        $this->savedPlan($this->consultation($other), 'Quinoa');
        $this->savedPlan($this->consultation($this->patient), 'Avena');

        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertOk()
            ->assertSee('Avena')
            ->assertDontSee('Quinoa')
            ->assertDontSee('Berta')
            ->assertDontSee('20/11/2026')
            ->assertSee('No tiene una próxima consulta programada.');

        // No portal route takes an id: there is nothing to change to reach another patient.
        $this->actingAs($this->account)->get('/portal/'.$other->id)->assertNotFound();
        $this->actingAs($this->account)->get(route('portal.home', ['patient' => $other->id]))->assertDontSee('Quinoa');
    }

    #[Group('US-6.3/AC-6')]
    public function test_staff_cannot_open_the_portal(): void
    {
        $this->actingAs(User::factory()->create())->get(route('portal.home'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('portal.home'))->assertForbidden();
    }
}
