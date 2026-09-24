<?php

namespace Tests\Feature\Access;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function validUser(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nutricionista Uno',
            'email' => 'Nutri@Example.com ',
            'rol_id' => Role::Doctor->value,
            'password' => 'clave1234',
            'password_confirmation' => 'clave1234',
        ], $overrides);
    }

    public function test_admin_creates_user_with_hashed_password_and_normalized_email(): void
    {
        $this->actingAs($this->admin)->post('/user', $this->validUser())
            ->assertRedirect('/user')
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'nutri@example.com')->firstOrFail();
        $this->assertSame(Role::Doctor, $user->role());
        $this->assertTrue(Hash::check('clave1234', $user->password));
    }

    public function test_user_validation_rules(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->admin)->post('/user', [
            'name' => 'Al',
            'email' => 'taken@example.com',
            'rol_id' => 9,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'rol_id', 'password']);
    }

    public function test_password_requires_letters_and_numbers(): void
    {
        $this->actingAs($this->admin)
            ->post('/user', $this->validUser(['password' => '12345678', 'password_confirmation' => '12345678']))
            ->assertSessionHasErrors('password');
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $this->actingAs($this->admin)->post('/user', $this->validUser(['name' => '']))
            ->assertSessionHasErrors(['name' => 'El campo nombre es obligatorio.']);
    }

    public function test_update_keeps_password_when_left_empty(): void
    {
        $doctor = User::factory()->create();
        $oldHash = $doctor->password;

        $this->actingAs($this->admin)->put("/user/{$doctor->id}", [
            'name' => 'Nuevo Nombre',
            'email' => $doctor->email,
            'rol_id' => Role::ChiefDoctor->value,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect('/user');

        $doctor->refresh();
        $this->assertSame('Nuevo Nombre', $doctor->name);
        $this->assertSame(Role::ChiefDoctor, $doctor->role());
        $this->assertSame($oldHash, $doctor->password);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $this->actingAs($this->admin)->put("/user/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'rol_id' => Role::Doctor->value,
        ])->assertSessionHasErrors('rol_id');

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin)->delete("/user/{$this->admin->id}")->assertForbidden();
        $this->assertModelExists($this->admin);
    }

    public function test_admin_deletes_other_user(): void
    {
        $doctor = User::factory()->create();

        $this->actingAs($this->admin)->delete("/user/{$doctor->id}")->assertRedirect('/user');
        $this->assertModelMissing($doctor);
    }

    public function test_doctor_cannot_create_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/user', $this->validUser(['rol_id' => Role::Admin->value]))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'nutri@example.com']);
    }
}
