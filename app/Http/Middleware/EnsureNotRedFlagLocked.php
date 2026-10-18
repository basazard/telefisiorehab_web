<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blocks every patient page (program, dashboard, new screening) while a red-flag stop is active. */
class EnsureNotRedFlagLocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $patient = $request->user()?->patient;

        if ($patient && $patient->isRedFlagLocked()) {
            return redirect()->route('patient.red-flag.stop');
        }

        return $next($request);
    }
}
