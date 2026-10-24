<?php

namespace App\Console\Commands;

use App\Support\Clinical\Register;
use App\Support\Clinical\ReviewDocuments;
use App\Support\Clinical\Vignettes;
use Illuminate\Console\Command;

/** PRD 10 W2: writes the worked cases, computed by the real clinical rules. */
class ClinicalVignettes extends Command
{
    protected $signature = 'clinical:vignettes {--path=prd/clinical/vignettes.md : Where to write the cases}';

    protected $description = 'Write the worked clinical cases (Indonesian) computed by the current rules';

    public function handle(): int
    {
        $cases = Vignettes::cases();

        file_put_contents(base_path($this->option('path')), ReviewDocuments::vignettes($cases, Register::load()['clinical_version']));
        $this->info('Written '.$this->option('path').' ('.count($cases).' cases).');

        return self::SUCCESS;
    }
}
