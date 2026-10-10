<?php

namespace Tests\Feature;

use App\Models\MacroRule;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MacroRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $chief;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chief = User::factory()->chiefDoctor()->create();
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Resistencia a la insulina por HOMA-IR',
            'advice' => 'Preferir alimentos de bajo índice glicémico.',
            'variable' => 'homa_ir',
            'operator' => '>',
            'value' => 2.5,
            'actions' => ['glycemic_load' => '80', 'carbohydrates' => '45', 'fats' => '', 'protein_g_per_kg' => null],
            'active' => '1',
            ...$overrides,
        ];
    }

    public function test_chief_doctor_creates_a_rule(): void
    {
        $this->actingAs($this->chief)->post('/macro-rules', $this->payload())
            ->assertRedirect(route('macro-rules.index'));

        $rule = MacroRule::firstOrFail();
        // MySQL JSON columns do not keep key order.
        $this->assertSame(['carbohydrates' => 45, 'glycemic_load' => 80], collect($rule->actions)->sortKeys()->all());
        $this->assertTrue($rule->active);
        $this->assertSame($this->chief->id, (int) $rule->created_by);

        $this->actingAs($this->chief)->get('/macro-rules')
            ->assertOk()
            ->assertSeeInOrder(['CFG-'.$rule->id, 'Resistencia a la insulina por HOMA-IR', 'HOMA-IR &gt; 2,5', 'Carbohidratos máximo 45 %', 'Carga glucémica máxima 80 por día', 'Activa'], false);
    }

    public function test_edit_and_deactivate(): void
    {
        $rule = MacroRule::create([...$this->payload(), 'actions' => ['glycemic_load' => 80], 'active' => true]);

        $this->actingAs($this->chief)->get("/macro-rules/{$rule->id}/edit")->assertOk()->assertSee('value="80"', false);

        $this->actingAs($this->chief)->put("/macro-rules/{$rule->id}", $this->payload([
            'operator' => '>=', 'value' => 3, 'actions' => ['sodium_mg' => 1500], 'active' => '0',
        ]))->assertRedirect(route('macro-rules.index'));

        $rule->refresh();
        $this->assertSame(['>=', 3.0, ['sodium_mg' => 1500], false], [$rule->operator, $rule->value, $rule->actions, $rule->active]);
        $this->assertSame('HOMA-IR ≥ 3', $rule->condition());
    }

    public function test_validation_follows_the_shared_catalog(): void
    {
        $this->actingAs($this->chief)->post('/macro-rules', $this->payload([
            'variable' => 'shoe_size', 'operator' => '!=', 'actions' => ['glycemic_load' => 10, 'carbohydrates' => 44.5, 'magic' => 1],
        ]))->assertSessionHasErrors(['variable', 'operator', 'actions', 'actions.glycemic_load', 'actions.carbohydrates']);

        $this->actingAs($this->chief)->post('/macro-rules', $this->payload(['actions' => ['glycemic_load' => '']]))
            ->assertSessionHasErrors(['actions' => 'Indique al menos una acción.']);

        $this->assertSame(0, MacroRule::count());
    }

    public function test_only_the_chief_doctor_manages_rules(): void
    {
        $rule = MacroRule::create([...$this->payload(), 'actions' => ['glycemic_load' => 80]]);
        $doctor = User::factory()->create();

        $this->actingAs($doctor)->get('/macro-rules')->assertForbidden();
        $this->actingAs($doctor)->post('/macro-rules', $this->payload())->assertForbidden();
        $this->actingAs($doctor)->delete("/macro-rules/{$rule->id}")->assertForbidden();

        $this->actingAs($this->chief)->delete("/macro-rules/{$rule->id}")->assertRedirect(route('macro-rules.index'));
        $this->assertSame(0, MacroRule::count());
    }

    public function test_active_rules_are_sent_to_the_expert_service(): void
    {
        config(['services.expert.url' => 'http://expert:8000', 'services.expert.token' => 't0k']);
        $active = MacroRule::create([...$this->payload(), 'actions' => ['glycemic_load' => 80, 'carbohydrates' => 45]]);
        MacroRule::create([...$this->payload(['name' => 'Inactiva']), 'actions' => ['sodium_mg' => 1500], 'active' => false]);
        $patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);
        Http::fake(['*' => Http::response(['findings' => [], 'macronutrients' => ['status' => 'indeterminado', 'rules' => [], 'notes' => []]])]);

        $this->actingAs(User::factory()->create())
            ->postJson("/diagnosis/{$patient->id}/macros", ['weight' => 70, 'size' => 165, 'age' => 35, 'physical_activity' => 1])
            ->assertOk();

        // MySQL JSON columns do not keep key order, so the actions are compared sorted.
        Http::assertSent(fn (Request $request) => collect(json_decode($request->body(), true)['macro_rules'])
            ->map(fn (array $rule) => ['actions' => collect($rule['actions'])->sortKeys()->all()] + $rule)
            ->all() === [[
                'actions' => ['carbohydrates' => 45, 'glycemic_load' => 80],
                'id' => 'CFG-'.$active->id,
                'title' => 'Resistencia a la insulina por HOMA-IR',
                'advice' => 'Preferir alimentos de bajo índice glicémico.',
                'variable' => 'homa_ir',
                'operator' => '>',
                'value' => 2.5,
            ]]);
    }
}
