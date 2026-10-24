<?php

namespace App\Console\Commands;

use App\Support\Clinical\Register;
use App\Support\Clinical\ReviewDocuments;
use Illuminate\Console\Command;

/** PRD 10 W2: writes the reviewer sheet from the clinical parameter register. */
class ClinicalReviewSheet extends Command
{
    protected $signature = 'clinical:review-sheet {--path=prd/clinical/review-sheet.md : Where to write the sheet}';

    protected $description = 'Write the clinical review sheet (Indonesian) from prd/clinical/parameters.json';

    public function handle(): int
    {
        $register = Register::load();
        $path = base_path($this->option('path'));

        file_put_contents($path, ReviewDocuments::reviewSheet($register['parameters'], $register['clinical_version']));
        $this->info('Written '.$this->option('path').' ('.count($register['parameters']).' parameters).');

        return self::SUCCESS;
    }
}
