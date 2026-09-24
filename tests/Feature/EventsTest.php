<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Events\DiagnosisCategoryConfirmed;
use App\Events\DiagnosisCreated;
use App\Events\PatientDeleted;
use App\Events\PatientRegistered;
use App\Events\UserAccountCreated;
use App\Events\UserRoleChanged;
use App\Listeners\RecordAuditTrail;
use App\Listeners\ScheduleModelRetraining;
use App\Listeners\SendAccountCreatedNotification;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_listeners_are_registered(): void
    {
        Event::fake();

        Event::assertListening(PatientRegistered::class, [RecordAuditTrail::class, 'handlePatientRegistered']);
        Event::assertListening(PatientDeleted::class, [RecordAuditTrail::class, 'handlePatientDeleted']);
        Event::assertListening(DiagnosisCreated::class, [RecordAuditTrail::class, 'handleDiagnosisCreated']);
        Event::assertListening(DiagnosisCategoryConfirmed::class, [RecordAuditTrail::class, 'handleDiagnosisCategoryConfirmed']);
        Event::assertListening(DiagnosisCategoryConfirmed::class, ScheduleModelRetraining::class);
        Event::assertListening(UserAccountCreated::class, SendAccountCreatedNotification::class);
        Event::assertListening(UserRoleChanged::class, [RecordAuditTrail::class, 'handleUserRoleChanged']);
    }

    public function test_patient_lifecycle_events(): void
    {
        Event::fake([PatientRegistered::class, PatientDeleted::class]);
        $doctor = User::factory()->create();

        $this->actingAs($doctor)->post('/patient', [
            'first_name' => 'Juan', 'last_name' => 'Pérez', 'rut' => '12.345.678-5',
            'address' => 'Calle 123', 'birthdate' => '1985-05-20', 'gender' => 'H',
        ]);
        $patient = Patient::firstOrFail();

        Event::assertDispatched(PatientRegistered::class,
            fn ($e) => $e->patient->is($patient) && $e->actor->is($doctor));

        $this->actingAs($doctor)->delete("/patient/{$patient->id}");

        Event::assertDispatched(PatientDeleted::class,
            fn ($e) => $e->patientId === $patient->id && $e->rut === '12345678-5' && $e->diagnosesDeleted === 0);
    }

    public function test_diagnosis_events_capture_the_previous_category(): void
    {
        Event::fake([DiagnosisCreated::class, DiagnosisCategoryConfirmed::class]);
        config(['services.inference.url' => null]);
        $this->seed(RulesSeeder::class);
        $doctor = User::factory()->create();
        $patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);

        $this->actingAs($doctor)->post('/diagnosis', [
            'id_patient' => $patient->id, 'weight' => 80, 'size' => 170, 'age' => 35,
            'physical_activity' => 2, 'imc' => 27.68,
        ]);
        $diagnosis = Diagnosis::firstOrFail();
        Event::assertDispatched(DiagnosisCreated::class, fn ($e) => $e->diagnosis->is($diagnosis));

        $this->actingAs($doctor)->put("/result/{$diagnosis->id}/rule", ['id_rule' => 4]);

        Event::assertDispatched(DiagnosisCategoryConfirmed::class, fn ($e) => $e->previousRule === 3
            && $e->previousSource === 'rules'
            && (int) $e->diagnosis->id_rule === 4
            && $e->changedCategory());
    }

    public function test_user_events(): void
    {
        Event::fake([UserAccountCreated::class, UserRoleChanged::class]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/user', [
            'name' => 'Nutri', 'email' => 'nutri@example.com', 'rol_id' => Role::Doctor->value,
            'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ]);
        $user = User::where('email', 'nutri@example.com')->firstOrFail();
        Event::assertDispatched(UserAccountCreated::class, fn ($e) => $e->user->is($user) && $e->actor->is($admin));

        // Same role: no event.
        $this->actingAs($admin)->put("/user/{$user->id}", ['name' => 'Nutri 2', 'email' => $user->email, 'rol_id' => Role::Doctor->value]);
        Event::assertNotDispatched(UserRoleChanged::class);

        $this->actingAs($admin)->put("/user/{$user->id}", ['name' => 'Nutri 2', 'email' => $user->email, 'rol_id' => Role::ChiefDoctor->value]);
        Event::assertDispatched(UserRoleChanged::class,
            fn ($e) => $e->from === Role::Doctor && $e->to === Role::ChiefDoctor && $e->actor->is($admin));
    }
}
