<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * PRD 10 W5: the release gate run by deploy.sh. When sign-off is required (production by default) the clinical rules
 * must all be approved and unchanged since approval; otherwise it only reports.
 */
class ClinicalGate extends Command
{
    protected $signature = 'clinical:gate';

    protected $description = 'Release gate: fail when clinical sign-off is required and the clinical rules are not fully approved';

    public function handle(): int
    {
        $version = config('clinical.version');

        if (! config('clinical.signoff_required')) {
            $this->warn("Clinical sign-off is not required here (CLINICAL_SIGNOFF_REQUIRED off). Rule set $version is not checked.");

            return self::SUCCESS;
        }

        $this->line("Checking clinical sign-off for rule set $version ...");
        if ($this->call('clinical:status', ['--strict' => true]) !== self::SUCCESS) {
            $this->error('RELEASE BLOCKED: clinical rules are not fully reviewed and approved (PRD 10).');

            return self::FAILURE;
        }

        $this->info("Clinical sign-off complete for rule set $version.");

        return self::SUCCESS;
    }
}
