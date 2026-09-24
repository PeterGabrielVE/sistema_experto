<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // The Now UI dashboard is built on Bootstrap 4; Laravel defaults to Tailwind.
        Paginator::useBootstrapFour();

        $this->defineGates();
    }

    /**
     * Permission matrix:
     *  - view-clinical:            any user with a valid role (patients, diagnoses, results, PDF)
     *  - manage-patients:          Doctor, Doctor Jefe
     *  - manage-diagnoses:         Doctor, Doctor Jefe
     *  - manage-clinical-content:  Doctor Jefe (rules, recommendations, schedules)
     * Account management lives in App\Policies\UserPolicy (administrators).
     */
    private function defineGates(): void
    {
        Gate::define('view-clinical', fn (User $user) => $user->role() !== null);
        Gate::define('manage-patients', fn (User $user) => $user->isDoctor());
        Gate::define('manage-diagnoses', fn (User $user) => $user->isDoctor());
        Gate::define('manage-clinical-content', fn (User $user) => $user->hasRole(Role::ChiefDoctor));
    }
}
