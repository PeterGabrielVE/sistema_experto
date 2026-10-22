<?php

namespace Tests\Feature\Portal;

use App\Models\AdherenceCheckin;
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
 * The menu of the day in the patient portal and the daily record of the plan (US-6.2).
 */
class PortalAdherenceTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();
        // Wednesday 21/10, 09:00 in Santiago (UTC-3).
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

    /**
     * A saved plan of 3 days: breakfast is Avena on day 1, Pan on day 2 and Quinoa on day 3.
     * The snack of the morning has no foods.
     */
    private function savedPlan(string $savedAt, ?Patient $patient = null): MealPlan
    {
        $diagnosis = Diagnosis::create([
            'id_patient' => ($patient ?? $this->patient)->id, 'age' => 41, 'weight' => 80, 'size' => 165, 'imc' => 29.4,
            'physical_activity' => 1, 'id_rule' => 3, 'result_pulgar' => 1800, 'carbohydrate' => 200, 'protein' => 90, 'lipido' => 60,
        ]);
        $day = fn (int $n, string $breakfast) => ['day' => $n, 'meals' => [
            ['key' => 'desayuno', 'label' => 'Desayuno', 'items' => [['food_id' => 1, 'name' => $breakfast, 'portions' => 1, 'grams' => 50]]],
            ['key' => 'colacion_matutina', 'label' => 'Colación matutina', 'items' => []],
            ['key' => 'almuerzo', 'label' => 'Almuerzo', 'items' => [['food_id' => 2, 'name' => 'Pollo', 'portions' => 2, 'grams' => 100]]],
        ]];
        $plan = MealPlan::create([
            'diagnosis_id' => $diagnosis->id, 'edited' => false,
            'plan' => ['status' => 'generado', 'days' => [$day(1, 'Avena'), $day(2, 'Pan'), $day(3, 'Quinoa')]],
        ]);
        $plan->forceFill(['updated_at' => $savedAt])->save();

        return $plan;
    }

    private function breakfastOn(string $localDate): ?string
    {
        $menu = app(AdherenceService::class)->menuFor($this->patient, Carbon::parse($localDate, 'America/Santiago'));

        return $menu ? collect($menu['meals'])->firstWhere('key', 'desayuno')['items'][0]['name'] : null;
    }

    #[Group('US-6.2/AC-1')]
    public function test_menu_rotates_from_the_day_the_plan_was_saved(): void
    {
        // Saved on 19/10 at 23:30 in Santiago (20/10 02:30 UTC): day 1 is 19/10 there.
        $this->savedPlan('2026-10-20 02:30:00');

        $this->assertNull($this->breakfastOn('2026-10-18')); // before the plan existed
        $this->assertSame('Avena', $this->breakfastOn('2026-10-19'));
        $this->assertSame('Pan', $this->breakfastOn('2026-10-20'));
        $this->assertSame('Quinoa', $this->breakfastOn('2026-10-21'));
        $this->assertSame('Avena', $this->breakfastOn('2026-10-22')); // starts again
        $this->assertSame('Quinoa', $this->breakfastOn('2026-10-27'));

        $menu = app(AdherenceService::class)->menuFor($this->patient, Carbon::parse('2026-10-21', 'America/Santiago'));
        $this->assertSame(3, $menu['day']);
        $this->assertSame(['desayuno', 'almuerzo'], array_column($menu['meals'], 'key')); // meals with foods only

        // Saving the plan again starts the rotation that day (D-4).
        $this->savedPlan('2026-10-21 12:00:00');
        $this->assertSame('Avena', $this->breakfastOn('2026-10-21'));
    }

    #[Group('US-6.2/AC-1')]
    public function test_portal_shows_todays_and_yesterdays_menu(): void
    {
        $this->savedPlan('2026-10-19 15:00:00'); // 19/10 is day 1: 20/10 day 2, 21/10 day 3

        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertOk()
            ->assertSeeInOrder([
                'Menú de hoy', 'miércoles 21/10', 'Día 3 del plan', 'Desayuno', 'Quinoa', 'Almuerzo', 'Pollo',
                'Menú de ayer', 'martes 20/10', 'Día 2 del plan', 'Desayuno', 'Pan',
                'Su plan alimentario',
            ])
            ->assertDontSee('Colación matutina: '); // a meal without foods is not listed
    }

    #[Group('US-6.2/AC-1')]
    public function test_today_is_the_clinic_day(): void
    {
        $this->savedPlan('2026-10-19 15:00:00');
        // 23:30 of 21/10 in Santiago is already 22/10 in UTC.
        Carbon::setTestNow('2026-10-22 02:30:00');

        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertSeeInOrder(['Menú de hoy', 'miércoles 21/10', 'Quinoa', 'Menú de ayer', 'martes 20/10', 'Pan']);
    }

    #[Group('US-6.2/AC-1')]
    public function test_yesterday_before_the_plan_was_saved_has_no_menu(): void
    {
        $this->savedPlan('2026-10-21 12:00:00'); // saved today

        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertSeeInOrder(['Menú de hoy', 'Día 1 del plan', 'Avena', 'Menú de ayer', 'Su plan aún no comenzaba ese día.']);
    }

    #[Group('US-6.2/AC-5')]
    public function test_without_a_saved_plan_there_is_nothing_to_record(): void
    {
        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertOk()
            ->assertSee('Su profesional aún no le ha guardado un plan alimentario.')
            ->assertDontSee('Menú de hoy')
            ->assertDontSee('Menú de ayer')
            ->assertDontSee('name="meals[', false);

        $this->assertNull(app(AdherenceService::class)->menuFor($this->patient, now()));
        $this->record('2026-10-21', ['desayuno' => 'done'])->assertStatus(422);
        $this->assertSame(0, AdherenceCheckin::count());
    }

    private function record(string $date, array $meals, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->account)->put(route('portal.adherence.update', $date), ['meals' => $meals]);
    }

    /**
     * @return array<string, string> meal_key => status of the patient's record of a day
     */
    private function marks(string $date, ?Patient $patient = null): array
    {
        return AdherenceCheckin::where('patient_id', ($patient ?? $this->patient)->id)
            ->whereDate('date', $date)->pluck('status', 'meal_key')->all();
    }

    #[Group('US-6.2/AC-1')]
    public function test_portal_shows_todays_menu_with_a_choice_per_meal(): void
    {
        $this->savedPlan('2026-10-19 15:00:00');

        $response = $this->actingAs($this->account)->get(route('portal.home'))->assertOk();

        $response->assertSee('action="'.route('portal.adherence.update', '2026-10-21').'"', false)
            ->assertSee('action="'.route('portal.adherence.update', '2026-10-20').'"', false);
        foreach (['done' => 'Cumplí', 'partial' => 'En parte', 'missed' => 'No cumplí', '' => 'Sin marcar'] as $value => $label) {
            $response->assertSee('name="meals[desayuno]" value="'.$value.'"', false)->assertSee($label);
        }
        $response->assertSee('name="meals[almuerzo]"', false)
            ->assertDontSee('name="meals[colacion_matutina]"', false); // no foods: nothing to mark
    }

    #[Group('US-6.2/AC-1')]
    public function test_patient_marks_each_meal(): void
    {
        $plan = $this->savedPlan('2026-10-19 15:00:00');

        $this->record('2026-10-21', ['desayuno' => 'done', 'almuerzo' => 'partial'])
            ->assertRedirect(route('portal.home'))
            ->assertSessionHas('status', 'Registro guardado.');

        $this->assertSame(['almuerzo' => 'partial', 'desayuno' => 'done'], collect($this->marks('2026-10-21'))->sortKeys()->all());
        $breakfast = AdherenceCheckin::where('meal_key', 'desayuno')->sole();
        $this->assertSame($plan->id, $breakfast->meal_plan_id);
        $this->assertSame(3, $breakfast->plan_day);
        $this->assertSame('Desayuno', $breakfast->meal_label);
        $this->assertSame($this->account->id, $breakfast->recorded_by);

        // The page shows what was marked.
        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertSee('name="meals[desayuno]" value="done" checked', false)
            ->assertSee('name="meals[almuerzo]" value="partial" checked', false);
    }

    #[Group('US-6.2/AC-2')]
    public function test_patient_corrects_or_unmarks_today_and_yesterday(): void
    {
        $this->savedPlan('2026-10-19 15:00:00');

        $this->record('2026-10-20', ['desayuno' => 'missed'])->assertRedirect(route('portal.home'));
        $this->record('2026-10-21', ['desayuno' => 'done', 'almuerzo' => 'done']);
        $this->record('2026-10-21', ['desayuno' => 'partial', 'almuerzo' => '']); // correct one, unmark the other

        $this->assertSame(['desayuno' => 'missed'], $this->marks('2026-10-20'));
        $this->assertSame(['desayuno' => 'partial'], $this->marks('2026-10-21')); // unmarked = no record
        $this->assertSame(2, AdherenceCheckin::count());
    }

    #[Group('US-6.2/AC-2')]
    public function test_older_or_future_days_cannot_be_recorded(): void
    {
        $this->savedPlan('2026-10-01 15:00:00');

        foreach (['2026-10-19', '2026-10-22', '2026-02-30'] as $date) {
            $this->record($date, ['desayuno' => 'done'])->assertStatus(422);
        }
        $this->actingAs($this->account)->put('/portal/cumplimiento/ayer', ['meals' => ['desayuno' => 'done']])->assertNotFound();

        $this->assertSame(0, AdherenceCheckin::count());
    }

    #[Group('US-6.2/AC-2')]
    public function test_meals_not_in_the_days_menu_are_rejected(): void
    {
        $this->savedPlan('2026-10-19 15:00:00');

        $this->record('2026-10-21', ['colacion_matutina' => 'done'])->assertSessionHasErrors('meals');
        $this->record('2026-10-21', ['cena' => 'done'])->assertSessionHasErrors('meals');
        $this->record('2026-10-21', ['desayuno' => 'quizas'])->assertSessionHasErrors('meals.desayuno');
        $this->record('2026-10-21', [])->assertSessionHasErrors('meals');

        $this->assertSame(0, AdherenceCheckin::count());
    }

    #[Group('US-6.2/AC-2')]
    public function test_days_are_the_clinic_days(): void
    {
        $this->savedPlan('2026-10-19 15:00:00');
        Carbon::setTestNow('2026-10-22 02:30:00'); // 21/10 23:30 in Santiago

        $this->record('2026-10-21', ['desayuno' => 'done'])->assertRedirect(route('portal.home'));
        $this->record('2026-10-22', ['desayuno' => 'done'])->assertStatus(422); // tomorrow in Chile

        $this->assertSame(['desayuno' => 'done'], $this->marks('2026-10-21'));
    }

    #[Group('US-6.2/AC-3')]
    public function test_a_patient_records_and_sees_only_their_own_meals(): void
    {
        $other = Patient::factory()->create(['first_name' => 'Berta']);
        $otherAccount = User::factory()->patient($other)->create();
        $this->savedPlan('2026-10-19 15:00:00');
        $this->savedPlan('2026-10-19 15:00:00', $other);

        $this->record('2026-10-21', ['desayuno' => 'done'], $otherAccount);
        $this->record('2026-10-21', ['almuerzo' => 'missed']);

        $this->assertSame(['desayuno' => 'done'], $this->marks('2026-10-21', $other));
        $this->assertSame(['almuerzo' => 'missed'], $this->marks('2026-10-21'));
        $this->actingAs($this->account)->get(route('portal.home'))
            ->assertDontSee('name="meals[desayuno]" value="done" checked', false)
            ->assertSee('name="meals[almuerzo]" value="missed" checked', false);

        // The team does not record for the patient.
        $this->record('2026-10-21', ['desayuno' => 'done'], User::factory()->create())->assertForbidden();
        $this->assertSame(['almuerzo' => 'missed'], $this->marks('2026-10-21'));
    }
}
