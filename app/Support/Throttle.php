<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Per-action limits for the pilot (PRD 11 E2). The limits (DRAFT) are in config('pilot.throttle').
 */
class Throttle
{
    /**
     * Counts one attempt of $action for $who. Returns null when allowed, or the Indonesian message to show when the
     * limit is reached.
     */
    public static function hit(string $action, string|int $who): ?string
    {
        [$max, $seconds] = config("pilot.throttle.$action");
        $key = "$action:$who";

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $wait = RateLimiter::availableIn($key);

            return 'Terlalu banyak permintaan. Silakan coba lagi dalam '.self::duration($wait).'.';
        }

        RateLimiter::hit($key, $seconds);

        return null;
    }

    private static function duration(int $seconds): string
    {
        return $seconds >= 120 ? ceil($seconds / 60).' menit' : $seconds.' detik';
    }
}
