<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * PRD 11 E1: proves the mail setup by sending one plain test message. On success it records the time in
 * storage/app/last-mail-test, which the go-live check (E10) reads. The password is never printed.
 */
class MailTest extends Command
{
    public const STAMP = 'last-mail-test';

    protected $signature = 'mail:test {address : Where to send the test message}';

    protected $description = 'Send a plain test email and record the time of the last success';

    /** storage/app/last-mail-test: the time of the last successful test. */
    public static function path(): string
    {
        return storage_path('app/'.self::STAMP);
    }

    public function handle(): int
    {
        $address = $this->argument('address');

        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->error('Not an email address.');

            return self::FAILURE;
        }

        $mailer = config('mail.default');
        $smtp = config("mail.mailers.$mailer", []);
        $this->line("Mailer: $mailer"
            .(($smtp['transport'] ?? null) === 'smtp' ? ' · host '.($smtp['host'] ?? '-').' · port '.($smtp['port'] ?? '-')
                .' · scheme '.(($smtp['scheme'] ?? null) ?: 'auto').' · user '.(($smtp['username'] ?? null) ?: '-') : ''));
        $this->line('From: '.config('mail.from.address'));

        if ($redirect = config('mail.always_to')) {
            $this->warn("MAIL_ALWAYS_TO is set: the message goes to $redirect, not to $address.");
        }
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("The '$mailer' mailer does not send real email.");
        }

        try {
            Mail::raw(
                "Ini email uji dari ".config('app.name')." (".config('app.env').").\n"
                ."Jika Anda menerimanya, pengiriman email berfungsi. Email ini tidak perlu dibalas.",
                fn ($message) => $message->to($address)->subject('Email uji '.config('app.name')),
            );
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e::class);
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        file_put_contents(self::path(), now()->toIso8601String());
        $this->info("Sent. Check the inbox (and the spam folder) of $address.");

        return self::SUCCESS;
    }
}
