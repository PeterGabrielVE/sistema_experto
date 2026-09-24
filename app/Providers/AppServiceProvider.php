<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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

        // Argon Dashboard is built on Bootstrap 5; Laravel defaults to Tailwind.
        Paginator::useBootstrapFive();

        // Authorization lives in App\Policies (auto-discovered: Models\X -> Policies\XPolicy).
    }
}
