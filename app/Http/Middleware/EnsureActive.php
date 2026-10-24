<?php

namespace App\Http\Middleware;

use App\Livewire\Actions\Logout;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActive
{
    /**
     * A deactivated account is logged out at its next request (PRD 09 S4.3), also inside an open session.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDeactivated()) {
            app(Logout::class)();

            if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                abort(403, 'Akun Anda dinonaktifkan.');
            }

            return redirect()->route('login')->with('status', 'Akun Anda dinonaktifkan. Hubungi admin klinik Anda.');
        }

        return $next($request);
    }
}
