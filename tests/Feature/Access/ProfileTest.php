<?php

namespace Tests\Feature\Access;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_escalate_own_role_from_profile(): void
    {
        $doctor = User::factory()->create();

        $this->actingAs($doctor)->put('/profile', [
            'name' => 'Doctor',
            'email' => $doctor->email,
            'rol_id' => Role::Admin->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame(Role::Doctor, $doctor->fresh()->role());
    }

    public function test_profile_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'other@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', ['name' => 'Nombre', 'email' => 'other@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'actual123']);

        $this->actingAs($user)->put('/profile/password', [
            'old_password' => 'incorrecta1',
            'password' => 'nueva1234',
            'password_confirmation' => 'nueva1234',
        ])->assertSessionHasErrors(['old_password' => 'La contraseña actual es incorrecta.']);

        $this->actingAs($user)->put('/profile/password', [
            'old_password' => 'actual123',
            'password' => 'nueva1234',
            'password_confirmation' => 'nueva1234',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nueva1234', $user->fresh()->password));
    }
}
