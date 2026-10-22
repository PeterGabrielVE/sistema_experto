<?php

use App\Http\Middleware\EnsurePatient;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(
            guests: fn () => route('login'),
            users: '/home',
        );

        // Staff side and patient portal (US-6.3); checked before route model binding.
        $middleware->alias([
            'staff' => EnsureStaff::class,
            'patient' => EnsurePatient::class,
        ]);
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureStaff::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsurePatient::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
