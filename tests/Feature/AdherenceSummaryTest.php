<?php

namespace Tests\Feature;

use App\Models\AdherenceCheckin;
use App\Models\ClinicalRecord;
use App\Models\Diagnosis;
use App\Models\MealPlan;
use App\Models\Patient;
use App\Models\User;
use App\Services\AdherenceService;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Weekly summary of how the patient follows the plan, in the clinical record (US-6.2/AC-4).
 */
#[Group('US-6.2/AC-4')]
class AdherenceSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        // Wednesday 21/10, 09:00 in Santiago. This week: Monday 19/10 to Sunday 25/10.
        Carbon::setTestNow('2026-10-21 12:00:00');
        config(['app.clinic_timezone' => 'America/Santiago']);
        $this->seed(RulesSeeder::class);

        $this->patient = Patient::factory()->create();
        ClinicalRecord::create(['patient_id' => $this->patient->id, 'consultation_reason' => 'Control']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function planSavedOn(string $utc): MealPlan
    {
        $diagnosis = Diagnosis::create([
            'id_patient' => $this->patient->id, 'age' => 41, 'weight' => 80, 'size' => 165, 'imc' => 29.4,
            'physical_activity' => 1, 'id_rule' => 3, 'result_pulgar' => 1800, 'carbohydrate' => 200, 'protein' => 90, 'lipido' => 60,
        ]);
        $plan = MealPlan::create(['diagnosis_id' => $diagnosis->id, 'edited' => false, 'plan' => ['days' => [['day' => 1, 'meals' => []]]]]);
        $plan->forceFill(['created_at' => $utc, 'updated_at' => $utc])->save();

        return $plan;
    }

    private function mark(string $date, string $meal, string $status): void
    {
        AdherenceCheckin::create([
            'patient_id' => $this->patient->id, 'date' => $date, 'plan_day' => 1,
            'meal_key' => $meal, 'meal_label' => ucfirst($meal), 'status' => $status,
        ]);
    }

    /**
     * @return array<string, array> the summary keyed by the Monday of each week
     */
    private function summary(int $weeks = 8): array
    {
        return collect(app(AdherenceService::class)->weeklySummary($this->patient, $weeks))->keyBy('from')->all();
    }

    public function test_weekly_percent_counts_partial_as_half_over_marked_meals(): void
    {
        $this->planSavedOn('2026-10-08 15:00:00');
        $this->mark('2026-10-20', 'desayuno', 'done');
        $this->mark('2026-10-20', 'almuerzo', 'partial');
        $this->mark('2026-10-20', 'cena', 'missed');
        $this->mark('2026-10-19', 'desayuno', 'done');
        $this->mark('2026-10-14', 'desayuno', 'missed');
        $this->mark('2026-10-14', 'almuerzo', 'missed');

        $week = $this->summary()['2026-10-19'];
        $this->assertSame('2026-10-25', $week['to']);
        $this->assertSame(62.5, $week['percent']); // (1 + 0,5 + 0 + 1) / 4
        $this->assertSame(4, $week['marked']);

        // Only "no cumplí": a real 0 %, not "sin dato".
        $this->assertSame(0.0, $this->summary()['2026-10-12']['percent']);
    }

    public function test_a_week_without_marks_is_no_data_not_zero(): void
    {
        $this->planSavedOn('2026-10-08 15:00:00');
        $this->mark('2026-10-20', 'desayuno', 'done');

        $week = $this->summary()['2026-10-12'];
        $this->assertNull($week['percent']);
        $this->assertSame(0, $week['marked']);
        $this->assertSame(7, $week['days_without_record']);
    }

    public function test_days_without_record_are_counted_from_the_first_plan_to_yesterday(): void
    {
        // First plan saved on Thursday 08/10 (local). A later plan does not move that date.
        $this->planSavedOn('2026-10-08 15:00:00');
        $this->planSavedOn('2026-10-16 15:00:00');
        $this->mark('2026-10-09', 'desayuno', 'done');
        $this->mark('2026-10-20', 'desayuno', 'done');
        $this->mark('2026-10-21', 'desayuno', 'done'); // today: counts in the percent, not as a day

        $summary = $this->summary();
        $this->assertSame(['2026-10-19', '2026-10-12', '2026-10-05'], array_keys($summary)); // newest first, from the first plan's week
        $this->assertSame(3, $summary['2026-10-05']['days_without_record']); // 08, 10 and 11 (09 recorded; before 08 there was no plan)
        $this->assertSame(1, $summary['2026-10-19']['days_without_record']); // 19 (20 recorded; 21 is today; 22-25 not yet)
        $this->assertSame(2, $summary['2026-10-19']['marked']);
    }

    public function test_only_the_last_weeks_are_listed(): void
    {
        $this->planSavedOn('2026-06-01 15:00:00');

        $summary = $this->summary();
        $this->assertCount(8, $summary);
        $this->assertSame('2026-10-19', array_key_first($summary));
        $this->assertSame('2026-08-31', array_key_last($summary));
    }

    public function test_scores_come_from_the_shared_file(): void
    {
        $shared = json_decode(file_get_contents(base_path('shared/clinical_thresholds.json')), true);
        $this->assertSame(['done' => 1, 'partial' => 0.5, 'missed' => 0], $shared['adherence']['scores']);
        $this->assertNotEmpty($shared['adherence']['source']);

        $this->planSavedOn('2026-10-08 15:00:00');
        $this->mark('2026-10-20', 'desayuno', 'partial');
        $this->mark('2026-10-20', 'almuerzo', 'done');
        $this->assertSame(75.0, $this->summary()['2026-10-19']['percent']);

        config(['clinical.adherence.scores.partial' => 0.25]);
        $this->assertSame(62.5, $this->summary()['2026-10-19']['percent']);
    }

    public function test_clinical_record_shows_the_weekly_summary(): void
    {
        $doctor = User::factory()->create();
        $url = route('patient.clinical-record.show', $this->patient);

        $this->actingAs($doctor)->get($url)
            ->assertSee('Cumplimiento del plan')
            ->assertSee('Sin plan alimentario guardado.');

        $this->planSavedOn('2026-10-08 15:00:00');
        $this->mark('2026-10-20', 'desayuno', 'done');
        $this->mark('2026-10-20', 'almuerzo', 'partial');

        $html = $this->actingAs($doctor)->get($url)
            ->assertOk()
            ->assertSeeInOrder(['Cumplimiento del plan', '19/10 – 25/10', '75,0 %', '2 comidas marcadas', '1 día sin registro'])
            ->assertSeeInOrder(['12/10 – 18/10', 'Sin dato', '0 comidas marcadas', '7 días sin registro'])
            ->getContent();
        $this->assertStringNotContainsString('0,0 %', $html); // a week without marks is never 0 %
    }
}
