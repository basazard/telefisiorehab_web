<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sending mail must never break a patient or staff action (PRD 11 E1): mail is synchronous (no queue worker on Hostinger),
 * so a mail server error is caught here, logged and swallowed.
 *
 * The log line is always `mail.failed` at error level (E6 alerts on it) with only the user id and the exception class:
 * never the address, a name or the exception message, which for SMTP errors often contains the recipient.
 */
class SafeMail
{
    /** Runs $send; returns true when it went out, false when it failed (and was logged). */
    public static function send(callable $send, ?int $userId = null): bool
    {
        try {
            $send();

            return true;
        } catch (Throwable $e) {
            Log::error('mail.failed', ['user_id' => $userId, 'exception' => $e::class]);

            return false;
        }
    }
}
