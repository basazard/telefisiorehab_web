<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response, error pages included (PRD 11 E2). A Content-Security-Policy is out of scope:
 * Livewire and Alpine would need nonces.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (self::https()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if (self::noindex()) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    /** The site runs on https (APP_URL): only then may browsers be told to insist on it. */
    public static function https(): bool
    {
        return str_starts_with((string) config('app.url'), 'https://');
    }

    /** The pilot and staging must never appear in search engines. */
    public static function noindex(): bool
    {
        return (bool) config('pilot.mode') || app()->environment('staging');
    }
}
