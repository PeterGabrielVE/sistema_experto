<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_series_uses_resource_format(): void
    {
        $viewer = User::factory()->create(['created_at' => '2026-03-10']);
        User::factory()->create(['created_at' => '2026-03-20']);
        User::factory()->create(['created_at' => '2026-12-01']);
        User::factory()->create(['created_at' => '2025-03-05']); // other year

        $response = $this->actingAs($viewer)->getJson('/users/chart?year=2026')->assertOk();

        $response->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.year', 2026)
            ->assertJsonPath('data.0', ['month' => 1, 'label' => 'ENE', 'count' => 0])
            ->assertJsonPath('data.2', ['month' => 3, 'label' => 'MAR', 'count' => 2])
            ->assertJsonPath('data.11', ['month' => 12, 'label' => 'DIC', 'count' => 1]);
    }

    public function test_defaults_to_current_year_and_validates_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/patients/chart')->assertOk()->assertJsonPath('meta.year', now()->year);
        $this->actingAs($user)->getJson('/diagnoses/chart?year=abc')->assertUnprocessable();
    }

    public function test_requires_a_role(): void
    {
        $this->actingAs(User::factory()->withoutRole()->create())->getJson('/users/chart')->assertForbidden();
    }
}
