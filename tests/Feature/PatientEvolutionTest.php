<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Services\LabResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Weight and HOMA-IR history of a patient (specs/EPIC-05-seguimiento-reportes/US-5.1-...).
 */
class PatientEvolutionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-10 12:00:00');
        $this->doctor = User::factory()->create();
        $this->patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function url(string $query = ''): string
    {
        return "/patient/{$this->patient->id}/evolution{$query}";
    }

    private function measurement(string $date, ?float $weight, array $attributes = []): void
    {
        $this->patient->measurements()->create([
            'measured_at' => $date, 'weight_kg' => $weight, 'created_by' => $this->doctor->id, ...$attributes,
        ]);
    }

    private function lab(string $date, ?float $glucose, ?float $insulin): void
    {
        $this->patient->labResults()->create([
            'taken_at' => $date, 'fasting_glucose' => $glucose, 'fasting_insulin' => $insulin, 'created_by' => $this->doctor->id,
        ]);
    }

    private function dates(array $series): array
    {
        return array_column($series['points'], 'date');
    }

    #[Group('US-5.1/AC-1')]
    #[Group('US-5.1/AC-6')]
    public function test_returns_weight_and_homa_ir_series_in_chronological_order(): void
    {
        $this->measurement('2026-08-20', 81.0);
        $this->measurement('2026-05-02', 84.5);
        $this->measurement('2026-06-01', null, ['waist_cm' => 98]); // no weight: not a point
        $this->lab('2026-09-15', 100, 9.4);
        $this->lab('2026-05-02', 105, 18.2);

        $response = $this->actingAs($this->doctor)->getJson($this->url())->assertOk();

        $this->assertSame(['key' => 'all', 'from' => null, 'to' => '2026-10-10'], $response->json('period'));
        $weight = $response->json('series.weight');
        $this->assertSame(['Peso', 'kg', true], [$weight['label'], $weight['unit'], $weight['enough_data']]);
        $this->assertEquals([['date' => '2026-05-02', 'value' => 84.5], ['date' => '2026-08-20', 'value' => 81.0]], $weight['points']);

        $homa = $response->json('series.homa_ir');
        $this->assertSame(['HOMA-IR', ''], [$homa['label'], $homa['unit']]);
        $this->assertSame(['2026-05-02', '2026-09-15'], $this->dates($homa));
        $this->assertEquals([4.72, 2.32], array_column($homa['points'], 'value'));
    }

    #[Group('US-5.1/AC-2')]
    public function test_exams_without_glucose_or_insulin_are_excluded_and_counted(): void
    {
        $this->lab('2026-03-01', 105, 18.2);
        $this->lab('2026-06-01', 98, null);
        $this->lab('2026-09-01', null, 12.0);

        $homa = $this->actingAs($this->doctor)->getJson($this->url())->assertOk()->json('series.homa_ir');

        $this->assertSame(['2026-03-01'], $this->dates($homa));
        $this->assertNotContains(0, array_column($homa['points'], 'value'));
        $this->assertSame(2, $homa['excluded']);
    }

    #[Group('US-5.1/AC-3')]
    public function test_includes_the_homa_ir_cut_off_and_flags_points_above_it(): void
    {
        $this->lab('2026-05-02', 105, 18.2); // 4.72
        $this->lab('2026-09-15', 90, 8.0);   // 1.78

        $homa = $this->actingAs($this->doctor)->getJson($this->url())->assertOk()->json('series.homa_ir');

        $this->assertEquals(config('clinical.insulin_resistance.homa_ir'), $homa['threshold']);
        $this->assertSame([true, false], array_column($homa['points'], 'above_threshold'));
    }

    #[Group('US-5.1/AC-4')]
    public function test_each_series_reports_whether_it_has_enough_data(): void
    {
        $empty = $this->actingAs($this->doctor)->getJson($this->url())->assertOk()->json('series');
        $this->assertSame([[], false, [], false], [
            $empty['weight']['points'], $empty['weight']['enough_data'], $empty['homa_ir']['points'], $empty['homa_ir']['enough_data'],
        ]);

        $this->measurement('2026-05-02', 84.5);
        $this->measurement('2026-08-20', 81.0);
        $this->lab('2026-05-02', 105, 18.2);

        $series = $this->actingAs($this->doctor)->getJson($this->url())->assertOk()->json('series');
        // A single exam does not hide the weight series.
        $this->assertSame([true, false], [$series['weight']['enough_data'], $series['homa_ir']['enough_data']]);
        $this->assertCount(1, $series['homa_ir']['points']);
    }

    #[Group('US-5.1/AC-7')]
    #[Group('US-5.1/AC-4')]
    public function test_period_filter_applies_to_both_series(): void
    {
        foreach (['2025-01-01', '2025-12-01', '2026-06-01', '2026-07-10', '2026-10-01'] as $date) {
            $this->measurement($date, 80);
            $this->lab($date, 105, 18.2);
        }

        $expected = [
            '3m' => ['2026-07-10', ['2026-07-10', '2026-10-01']], // the first day of the period is included
            '6m' => ['2026-04-10', ['2026-06-01', '2026-07-10', '2026-10-01']],
            '12m' => ['2025-10-10', ['2025-12-01', '2026-06-01', '2026-07-10', '2026-10-01']],
            'all' => [null, ['2025-01-01', '2025-12-01', '2026-06-01', '2026-07-10', '2026-10-01']],
        ];

        foreach ($expected as $period => [$from, $dates]) {
            $response = $this->actingAs($this->doctor)->getJson($this->url("?period={$period}"))->assertOk();

            $this->assertSame([$period, $from, '2026-10-10'], array_values($response->json('period')), $period);
            $this->assertSame($dates, $this->dates($response->json('series.weight')), $period);
            $this->assertSame($dates, $this->dates($response->json('series.homa_ir')), $period);
        }

        // Without a period the whole history is returned.
        $this->assertCount(5, $this->actingAs($this->doctor)->getJson($this->url())->json('series.weight.points'));
    }

    #[Group('US-5.1/AC-7')]
    public function test_rejects_an_unknown_period(): void
    {
        $this->actingAs($this->doctor)->getJson($this->url('?period=2y'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('period');
    }

    #[Group('US-5.1/AC-5')]
    public function test_administrator_gets_403(): void
    {
        $this->lab('2026-05-02', 105, 18.2);

        $this->getJson($this->url())->assertUnauthorized(); // guest, before acting as anyone
        $this->actingAs(User::factory()->admin()->create())->getJson($this->url())->assertForbidden();
    }

    #[Group('US-5.1/AC-6')]
    public function test_homa_ir_values_match_the_lab_results_page(): void
    {
        $this->lab('2026-02-14', 96, 6.4);
        $this->lab('2026-05-02', 105, 18.2);
        $this->lab('2026-09-15', 112, 23.7);

        $homa = $this->actingAs($this->doctor)->getJson($this->url())->assertOk()->json('series.homa_ir');
        $page = array_column(app(LabResultService::class)->series($this->patient), 'homa_ir');

        $this->assertEquals($page, array_column($homa['points'], 'value'));
    }
}
