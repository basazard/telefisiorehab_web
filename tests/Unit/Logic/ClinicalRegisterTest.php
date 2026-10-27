<?php

use App\Support\Clinical\Register;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

// PRD 10 W1: the clinical parameter register and the code stay in sync.

uses(TestCase::class);

afterEach(fn () => Register::usePath(null));

/** @return array<string, string> tag id => file, for every "@clinical P-XXX" in the code */
function clinicalTags(): array
{
    $tags = [];
    $files = Finder::create()->files()->in([app_path(), resource_path('views')])->name(['*.php']);

    foreach ($files as $file) {
        preg_match_all('/@clinical\s+(P-[A-Z]{2}-\d{2,})/', $file->getContents(), $m);
        foreach ($m[1] as $id) {
            expect($tags)->not->toHaveKey($id, "Tag $id appears twice");
            $tags[$id] = $file->getRealPath();
        }
    }

    return $tags;
}

it('has well-formed entries with unique ids', function () {
    $entries = Register::entries();
    $ids = array_column($entries, 'id');

    expect($ids)->toHaveCount(count(array_unique($ids)));

    foreach ($entries as $e) {
        expect($e)->toHaveKeys(['id', 'module', 'label', 'value', 'unit', 'location', 'prd_ref', 'rationale', 'source', 'status', 'reviewer', 'reviewed_on', 'comment', 'approved_hash'])
            ->and($e['id'])->toMatch('/^P-[A-Z]{2}-\d{2,}$/')
            ->and($e['status'])->toBeIn(Register::STATUSES)
            ->and(file_exists(base_path($e['prd_ref'])))->toBeTrue("{$e['id']}: {$e['prd_ref']} does not exist")
            ->and(filled($e['rationale']) && filled($e['source']))->toBeTrue("{$e['id']}: rationale and source must say something, or that they are unknown");
    }
});

it('has a tag in the code for every entry, and an entry for every tag', function () {
    $tags = clinicalTags();
    $entries = collect(Register::entries())->filter(fn ($e) => $e['location'] !== null)->keyBy('id');

    expect(array_keys($tags))->toEqualCanonicalizing($entries->keys()->all());

    foreach ($entries as $id => $e) {
        expect(realpath(Register::file($e['location'])))->toBe($tags[$id], "$id is tagged in another file than its location");
    }
});

it('holds the values that are in the code', function () {
    foreach (Register::entries() as $e) {
        if (Register::isSource($e['location']) || $e['status'] === 'removed') {
            continue;                                   // rules and text blocks are checked by hash
        }

        expect($e['value'])->toBe(Register::resolve($e['location']), "{$e['id']} differs from {$e['location']}");
    }
});

it('resolves every location', function () {
    foreach (Register::entries() as $e) {
        expect(Register::hash($e['location']))->toHaveLength(64);
    }
});

it('records who approved a parameter, when, and the code it approved', function () {
    $decisions = [];
    $file = fopen(base_path('prd/clinical/decisions.csv'), 'r');
    $header = fgetcsv($file);
    while ($row = fgetcsv($file)) {
        $decisions[$row[0]] = array_combine($header, $row);
    }
    fclose($file);

    foreach (Register::entries() as $e) {
        if ($e['status'] === 'draft') {
            expect($e['approved_hash'])->toBeNull("{$e['id']} is draft but has an approval hash");

            continue;
        }

        // only a signed decision approves a parameter (PRD 10 W4)
        expect($decisions[$e['id']]['decision'] ?? null)->toBe('Setuju', "{$e['id']} is approved without a Setuju decision")
            ->and($e['reviewer'])->toBe($decisions[$e['id']]['reviewer'])
            ->and($e['reviewed_on'])->toBe($decisions[$e['id']]['date'])
            ->and($e['approved_hash'])->toBe(Register::hash($e['location']), "{$e['id']}: the code changed after approval");
    }
});

// ---------------------------------------------------------------- clinical:status

function registerFile(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'reg');
    file_put_contents($path, json_encode(['schema' => 1, 'clinical_version' => 'test', 'parameters' => $entries]));
    Register::usePath($path);

    return $path;
}

function approvedEntry(array $o = []): array
{
    return $o + [
        'id' => 'P-RF-17', 'module' => 'red_flags', 'label' => 'x', 'value' => 9, 'unit' => null,
        'location' => 'RedFlags::PAIN_STOP_THRESHOLD', 'prd_ref' => 'prd/03-red-flag-screening.md', 'rationale' => 'x', 'source' => 'x',
        'status' => 'approved', 'reviewer' => 'x', 'reviewed_on' => '2026-01-01', 'comment' => null,
        'approved_hash' => Register::hash('RedFlags::PAIN_STOP_THRESHOLD'),
    ];
}

it('fails --strict while parameters are draft', function () {
    registerFile([approvedEntry(), approvedEntry(['id' => 'P-RF-18', 'status' => 'draft', 'approved_hash' => null])]);

    expect(Artisan::call('clinical:status', ['--strict' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('not reviewed yet');

    expect(Artisan::call('clinical:status'))->toBe(0);
});

it('passes --strict when everything is approved and unchanged', function () {
    registerFile([approvedEntry()]);

    expect(Artisan::call('clinical:status', ['--strict' => true]))->toBe(0);
});

it('notices code that changed after approval', function () {
    registerFile([approvedEntry(['approved_hash' => hash('sha256', '8')])]);

    expect(Artisan::call('clinical:status', ['--strict' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('P-RF-17: code changed after approval');
});

it('notices a location that no longer exists', function () {
    registerFile([approvedEntry(['location' => 'RedFlags::NOT_THERE'])]);

    expect(Artisan::call('clinical:status'))->toBe(1)
        ->and(Artisan::output())->toContain('not found');
});

it('hashes rules and text blocks from the code', function () {
    expect(Register::resolve('RedFlags::evaluate()'))->toContain('PAIN_STOP_THRESHOLD')
        ->and(Register::resolve('resources/views/livewire/pages/patient/red-flag-stop.blade.php#P-TX-01'))->toContain('112')
        ->and(Register::hash('RedFlags::evaluate()'))->not->toBe(Register::hash('RedFlags::allAnswered()'));
});
