<?php

namespace Tests\Feature\Access;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Recommendation;
use App\Models\Rule;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private function patientBy(User $author): Patient
    {
        return Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => fake()->unique()->numerify('########').'-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01', 'created_by' => $author->id,
        ]);
    }

    public function test_only_author_or_chief_doctor_can_delete_a_patient(): void
    {
        $author = User::factory()->create();
        $patient = $this->patientBy($author);

        $this->assertTrue($author->can('delete', $patient));
        $this->assertTrue(User::factory()->chiefDoctor()->create()->can('delete', $patient));
        $this->assertFalse(User::factory()->create()->can('delete', $patient));
        $this->assertFalse(User::factory()->admin()->create()->can('delete', $patient));

        // Any doctor may still update it: patients are shared by the team.
        $this->assertTrue(User::factory()->create()->can('update', $patient));
    }

    public function test_other_doctor_gets_403_with_reason(): void
    {
        $patient = $this->patientBy(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete("/patient/{$patient->id}")
            ->assertForbidden()
            ->assertSee('Solo quien registró al paciente o un Doctor Jefe puede eliminarlo.');

        $this->assertModelExists($patient);
    }

    public function test_diagnosis_abilities(): void
    {
        $diagnosis = new Diagnosis;

        $this->assertTrue(User::factory()->create()->can('confirmCategory', $diagnosis));
        $this->assertFalse(User::factory()->admin()->create()->can('confirmCategory', $diagnosis));
        $this->assertTrue(User::factory()->admin()->create()->can('view', $diagnosis));
        $this->assertFalse(User::factory()->withoutRole()->create()->can('view', $diagnosis));
    }

    public function test_clinical_content_is_for_chief_doctor_and_categories_are_fixed(): void
    {
        $chief = User::factory()->chiefDoctor()->create();
        $doctor = User::factory()->create();

        foreach ([Recommendation::class, Schedule::class] as $model) {
            $this->assertTrue($chief->can('create', $model));
            $this->assertTrue($chief->can('delete', new $model));
            $this->assertFalse($doctor->can('viewAny', $model));
        }

        $this->assertTrue($chief->can('viewAny', Rule::class));
        $this->assertFalse($chief->can('create', Rule::class));
        $this->assertFalse($chief->can('delete', new Rule));
    }
}
