<?php

namespace App\Support;

/** Who may register (PRD 11 E3): config('pilot.registration_mode'). */
class Registration
{
    public const OPEN = 'open';
    public const CLINIC_CODE = 'clinic_code';
    public const CLOSED = 'closed';

    /** An unknown value counts as clinic_code, the safe default. */
    public static function mode(): string
    {
        $mode = config('pilot.registration_mode');

        return in_array($mode, [self::OPEN, self::CLINIC_CODE, self::CLOSED], true) ? $mode : self::CLINIC_CODE;
    }

    /** Whether the register page and its links are offered at all. */
    public static function allowed(): bool
    {
        return self::mode() !== self::CLOSED;
    }

    public static function codeRequired(): bool
    {
        return self::mode() === self::CLINIC_CODE;
    }
}
