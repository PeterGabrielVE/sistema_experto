<?php

namespace Tests\Feature\Access;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public static function matrix(): array
    {
        // [route, admin, doctor, chief doctor, no role]
        return [
            'users list' => ['/user', 200, 403, 403, 403],
            'create user form' => ['/user/create', 200, 403, 403, 403],
            'patients list' => ['/patient', 200, 200, 200, 403],
            'create patient form' => ['/patient/create', 403, 200, 200, 403],
            'rules' => ['/rules', 403, 403, 200, 403],
            'recommendations' => ['/recommendation', 403, 403, 200, 403],
            'schedules' => ['/schedule', 403, 403, 200, 403],
            'profile' => ['/profile', 200, 200, 200, 200],
        ];
    }

    #[DataProvider('matrix')]
    public function test_permission_matrix(string $uri, int $admin, int $doctor, int $chief, int $noRole): void
    {
        foreach ([
            [User::factory()->admin()->create(), $admin],
            [User::factory()->create(), $doctor],
            [User::factory()->chiefDoctor()->create(), $chief],
            [User::factory()->withoutRole()->create(), $noRole],
        ] as [$user, $expected]) {
            $this->actingAs($user)->get($uri)->assertStatus($expected);
        }
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/patient')->assertRedirect('/login');
        $this->get('/user')->assertRedirect('/login');
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('register'));
        $this->get('/register')->assertRedirect('/login');
        $this->post('/register', [
            'name' => 'Intruso', 'email' => 'x@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertStatus(405);
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_admin_cannot_modify_patients(): void
    {
        $patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete("/patient/{$patient->id}")
            ->assertForbidden();

        $this->assertModelExists($patient);
    }
}
