<?php

namespace Tests\Feature\Portal;

use App\Enums\Role;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Patients with a portal account never reach the staff side (US-6.3/AC-6): every staff route,
 * the dashboard and the API answer 403, and the policies deny them too.
 */
#[Group('US-6.3/AC-6')]
class PortalIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** Authenticated routes shared by both sides (no data behind them). */
    private const SHARED = ['logout', 'password/confirm'];

    private Patient $patient;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = Patient::factory()->create();
        $this->account = User::factory()->patient($this->patient)->create();
    }

    /**
     * @return array<int, RoutingRoute>
     */
    private function authenticatedRoutes(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn (RoutingRoute $route) => array_intersect(['auth', 'auth:api'], $route->gatherMiddleware())
                && ! in_array($route->uri(), self::SHARED, true),
        ));
    }

    public function test_every_authenticated_route_says_which_side_it_belongs_to(): void
    {
        $routes = $this->authenticatedRoutes();
        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $this->assertNotEmpty(
                array_intersect(['staff', 'patient'], $route->gatherMiddleware()),
                "La ruta {$route->uri()} no declara si es del equipo (staff) o del portal (patient).",
            );
        }
    }

    public function test_patient_gets_403_on_every_staff_route(): void
    {
        $other = Patient::factory()->create();
        $checked = 0;

        foreach ($this->authenticatedRoutes() as $route) {
            if (! in_array('staff', $route->gatherMiddleware(), true)) {
                continue;
            }
            // Their own record and another patient's: the answer is the same, before any lookup.
            foreach ([$this->patient, $other] as $target) {
                $uri = '/'.ltrim(preg_replace('/\{[^}]+\}/', (string) $target->id, $route->uri()), '/');
                foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                    $guard = str_starts_with($route->uri(), 'api/') ? 'api' : 'web';
                    $this->actingAs($this->account, $guard)
                        ->json($method, $uri)
                        ->assertForbidden();
                    $checked++;
                }
            }
        }

        $this->assertGreaterThan(50, $checked);
    }

    public function test_patient_is_denied_by_the_policies_too(): void
    {
        foreach (['viewAny', 'create'] as $ability) {
            $this->assertFalse($this->account->can($ability, Patient::class), "Patient::{$ability}");
        }
        $this->assertFalse($this->account->can('viewAny', Diagnosis::class));
        $this->assertFalse($this->account->can('view', $this->patient));
        $this->assertFalse($this->account->can('viewClinicalRecord', $this->patient));
        $this->assertTrue($this->account->isPatient());
        $this->assertFalse($this->account->isStaff());
        $this->assertTrue(User::factory()->create()->isStaff());
        $this->assertTrue(User::factory()->admin()->create()->isStaff());
    }

    public function test_patient_cannot_get_an_api_token(): void
    {
        $this->account->forceFill(['password' => 'secret123'])->save();

        $this->postJson('/api/v1/tokens', ['email' => $this->account->email, 'password' => 'secret123'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', __('auth.failed')); // same answer as wrong credentials

        $this->assertNull($this->account->refresh()->api_token);
    }

    public function test_user_management_does_not_offer_nor_touch_patient_accounts(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('user.index'))
            ->assertOk()
            ->assertDontSee($this->account->email);
        $this->actingAs($admin)->get(route('user.create'))
            ->assertOk()
            ->assertSee('value="'.Role::Doctor->value.'"', false)
            ->assertDontSee('value="'.Role::Patient->value.'"', false);

        $this->actingAs($admin)->post(route('user.store'), [
            'name' => 'Cuenta falsa', 'email' => 'falsa@correo.cl', 'rol_id' => Role::Patient->value,
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('rol_id');
        $this->assertDatabaseMissing('users', ['email' => 'falsa@correo.cl']);

        // Patient accounts are managed from the clinical record, not here.
        $this->actingAs($admin)->get(route('user.edit', $this->account))->assertForbidden();
        $this->actingAs($admin)->delete(route('user.destroy', $this->account))->assertForbidden();
        $this->assertModelExists($this->account);
    }

    public function test_deleting_the_patient_deletes_the_account(): void
    {
        $this->assertTrue($this->patient->account->is($this->account));
        $this->assertTrue($this->account->patient->is($this->patient));

        $this->patient->delete();

        $this->assertModelMissing($this->account);
    }
}
