<?php

use App\Http\Middleware\EnsureActive;
use App\Http\Middleware\EnsureConsent;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\EnsureNotRedFlagLocked;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'profile.complete' => EnsureProfileComplete::class,
            'redflag.clear' => EnsureNotRedFlagLocked::class,
            'consent' => EnsureConsent::class,
        ]);

        // deactivated staff are logged out on every request, Livewire updates included
        $middleware->web(append: EnsureActive::class);

        // security headers on every response, error pages included (PRD 11 E2)
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A database error message contains the query's values (passwords, health data). Log the SQL with its
        // placeholders only (PRD 11 E2).
        $exceptions->report(function (QueryException $e) {
            Log::error('database.error', [
                'sql' => $e->getSql(),
                'code' => $e->getCode(),
                'connection' => $e->getConnectionName(),
            ]);
        })->stop();
    })->create();
