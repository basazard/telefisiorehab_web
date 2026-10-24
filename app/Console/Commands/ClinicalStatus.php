<?php

namespace App\Console\Commands;

use App\Support\Clinical\Register;
use Illuminate\Console\Command;
use Throwable;

/** PRD 10 W1: the state of the clinical parameter register. With --strict it fails while anything is unreviewed. */
class ClinicalStatus extends Command
{
    protected $signature = 'clinical:status {--strict : Exit with code 1 if anything is draft or changed, or an approved value changed in the code}';

    protected $description = 'Show the clinical parameter register: counts per module and status, drafts, and approved values that changed in the code';

    public function handle(): int
    {
        $entries = Register::entries();
        $problems = [];

        // counts per module and status
        $modules = [];
        foreach ($entries as $e) {
            $modules[$e['module']][$e['status']] = ($modules[$e['module']][$e['status']] ?? 0) + 1;
        }

        $rows = [];
        foreach ($modules as $module => $counts) {
            $rows[] = [$module, ...array_map(fn ($s) => $counts[$s] ?? 0, Register::STATUSES), array_sum($counts)];
        }
        $this->table(['module', ...Register::STATUSES, 'total'], $rows);
        $this->line('Clinical version: '.(Register::load()['clinical_version'] ?? '-').' · '.count($entries).' parameters');

        // every location must still exist; approved values must not have changed since approval
        foreach ($entries as $e) {
            try {
                $hash = Register::hash($e['location']);
            } catch (Throwable $ex) {
                $problems[] = "{$e['id']}: location {$e['location']} not found ({$ex->getMessage()})";

                continue;
            }

            if ($e['status'] === 'approved' && $e['approved_hash'] !== $hash) {
                $problems[] = "{$e['id']}: code changed after approval ({$e['location']})";
            }
        }

        $unreviewed = array_values(array_filter($entries, fn ($e) => in_array($e['status'], ['draft', 'changed'], true)));

        if ($unreviewed) {
            $this->newLine();
            $this->warn(count($unreviewed).' parameters are not reviewed yet:');
            foreach ($unreviewed as $e) {
                $this->line("  {$e['id']}  [{$e['status']}]  {$e['label']}");
            }
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        if ($this->option('strict') && ($unreviewed || $problems)) {
            $this->error('Clinical sign-off incomplete: '.count($unreviewed).' unreviewed, '.count($problems).' problems.');

            return self::FAILURE;
        }

        return $problems ? self::FAILURE : self::SUCCESS;
    }
}
