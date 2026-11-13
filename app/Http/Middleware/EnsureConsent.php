<?php

namespace App\Http\Middleware;

use App\Support\Legal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsent
{
    /**
     * Patients and staff accept the current terms, privacy and data statements before any other app page (PRD 11 E4).
     * Also runs on Livewire updates of those pages (persistent middleware). Logout, /privasi, /syarat and the consent page
     * itself are not behind it.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! Legal::hasConsented($user)) {
            if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                abort(403, 'Persetujuan diperlukan.');
            }

            return redirect()->guest(route('consent'));
        }

        return $next($request);
    }
}
