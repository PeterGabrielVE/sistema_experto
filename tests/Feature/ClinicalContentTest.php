<?php

namespace Tests\Feature;

use App\Models\Recommendation;
use App\Models\Rule;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalContentTest extends TestCase
{
    use RefreshDatabase;

    private User $chief;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chief = User::factory()->chiefDoctor()->create();
    }

    public function test_recommendation_crud_with_validation(): void
    {
        $rule = Rule::create(['name' => 'Normal', 'min' => '18.5', 'max' => '24.9']);

        $this->actingAs($this->chief)->post('/recommendation', ['id_rule' => 99, 'description' => 'x'])
            ->assertSessionHasErrors(['id_rule', 'description']);

        $this->actingAs($this->chief)->post('/recommendation', [
            'id_rule' => $rule->id, 'description' => '  Comer más verduras  ',
        ])->assertRedirect('/recommendation');

        $recommendation = Recommendation::firstOrFail();
        $this->assertSame('Comer más verduras', $recommendation->description);

        $this->actingAs($this->chief)->put("/recommendation/{$recommendation->id}", ['description' => 'Caminar 30 minutos'])
            ->assertRedirect('/recommendation');
        $this->assertSame('Caminar 30 minutos', $recommendation->fresh()->description);
        $this->assertSame($rule->id, (int) $recommendation->fresh()->id_rule);

        $this->actingAs($this->chief)->get("/recommendation/{$rule->id}")->assertOk()->assertSee('Caminar 30 minutos');

        $this->actingAs($this->chief)->delete("/recommendation/{$recommendation->id}")->assertRedirect('/recommendation');
        $this->assertModelMissing($recommendation);
    }

    public function test_schedule_times_must_be_in_order(): void
    {
        $this->actingAs($this->chief)->post('/schedule', [
            'breakfast' => '13:00', 'lunch' => '12:00', 'dinner' => 'tarde',
        ])->assertSessionHasErrors(['lunch', 'dinner']);

        $this->actingAs($this->chief)->post('/schedule', [
            'breakfast' => '08:00', 'lunch' => '13:30', 'dinner' => '20:00',
        ])->assertRedirect('/schedule')->assertSessionHasNoErrors();

        $this->assertSame('', Schedule::firstOrFail()->notes);
    }

    public function test_missing_records_return_404(): void
    {
        $this->actingAs($this->chief)->get('/recommendation/999/edit')->assertNotFound();
        $this->actingAs($this->chief)->get('/schedule/999/edit')->assertNotFound();
    }
}
