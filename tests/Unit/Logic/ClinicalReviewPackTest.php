<?php

use App\Enums\PhysioDomain;
use App\Enums\RiskTier;
use App\Support\Clinical\Register;
use App\Support\Clinical\ReviewDocuments;
use App\Support\Clinical\Vignettes;
use App\Support\Onboarding\Instruments;
use App\Support\Onboarding\Monitoring;
use App\Support\Onboarding\Questionnaires;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

// PRD 10 W2: the reviewer sheet and the worked cases.

uses(TestCase::class);

function sheet(): string
{
    $register = Register::load();

    return ReviewDocuments::reviewSheet($register['parameters'], $register['clinical_version']);
}

function vignettesText(): string
{
    return ReviewDocuments::vignettes(Vignettes::cases(), Register::load()['clinical_version']);
}

/** Item wording and answer choices of the instruments in the code: none of it may appear in the reviewer pack. */
function instrumentWording(): array
{
    $texts = [];
    $walk = function ($v) use (&$walk, &$texts) {
        if (is_array($v)) {
            array_walk($v, $walk);
        } elseif (is_string($v) && mb_strlen($v) > 12) {
            $texts[] = $v;
        }
    };

    foreach (['LEFS_ITEMS', 'LEFS_CHOICES', 'ODI_ITEMS', 'ODI_CHOICES', 'SPADI_PAIN', 'SPADI_DISABILITY', 'BARTHEL', 'STEADI', 'MMRC', 'BORG'] as $c) {
        $walk(constant(Instruments::class.'::'.$c));
    }
    foreach (['LEFS_ITEMS', 'LEFS_CHOICES', 'ODI_ITEMS', 'ODI_CHOICES'] as $c) {
        $walk(constant(Questionnaires::class.'::'.$c));
    }

    return array_values(array_unique($texts));
}

it('is deterministic', function () {
    expect(sheet())->toBe(sheet())->and(vignettesText())->toBe(vignettesText());
});

it('keeps the committed files up to date (run clinical:review-sheet and clinical:vignettes)', function () {
    $read = fn (string $f) => str_replace("
", "
", file_get_contents(base_path($f)));      // git may convert line endings

    expect($read('prd/clinical/review-sheet.md'))->toBe(sheet())
        ->and($read('prd/clinical/vignettes.md'))->toBe(vignettesText());
});

it('lists every register entry with a decision box, and has the sign-off block', function () {
    $sheet = sheet();

    foreach (Register::entries() as $e) {
        expect($sheet)->toContain("| {$e['id']} |");
    }

    expect(substr_count($sheet, '[ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus'))->toBe(count(Register::entries()))
        ->and($sheet)->toContain('Nomor STR / SIP')->toContain('Tanda tangan')->toContain('Pertanyaan untuk pemilik dan pengacara');
});

it('has at least 20 cases, each with a verdict line', function () {
    $cases = Vignettes::cases();

    expect(count($cases))->toBeGreaterThanOrEqual(20)
        ->and(substr_count(vignettesText(), '[ ] Sesuai  [ ] Tidak sesuai, karena'))->toBe(count($cases));
});

it('covers every domain, every risk tier, the red-flag stops and the monitoring outcomes', function () {
    $text = vignettesText();

    foreach (PhysioDomain::cases() as $domain) {
        expect($text)->toContain("Domain: **{$domain->label()}**");
    }
    foreach (RiskTier::cases() as $tier) {
        expect($text)->toContain("Kategori risiko: **{$tier->label()}**");
    }
    foreach (Monitoring::OUTCOMES as $label) {
        expect($text)->toContain("Keputusan: **$label**");
    }

    expect($text)->toContain('Red flag: program DIHENTIKAN')
        ->toContain('Skor nyeri sangat tinggi')                // pain 9
        ->toContain('Kegawatdaruratan: program DIHENTIKAN')    // SpO2 and wound infection
        ->toContain('Kelompok khusus: Screening kehamilan')
        ->toContain('Screening pasca operasi')
        ->toContain('Screening tumbuh kembang')
        ->toContain('Screening risiko jatuh');
});

it('never contains the wording of an instrument', function () {
    $pack = sheet().vignettesText();
    $wording = instrumentWording();

    expect(count($wording))->toBeGreaterThan(50);
    foreach ($wording as $text) {
        expect(str_contains($pack, $text))->toBeFalse("Instrument wording found: $text");
    }
});

it('writes both files through the commands', function () {
    $sheet = 'storage/framework/testing/review-sheet.md';
    $cases = 'storage/framework/testing/vignettes.md';
    @mkdir(base_path('storage/framework/testing'), 0777, true);

    expect(Artisan::call('clinical:review-sheet', ['--path' => $sheet]))->toBe(0)
        ->and(Artisan::call('clinical:vignettes', ['--path' => $cases]))->toBe(0)
        ->and(file_get_contents(base_path($sheet)))->toBe(sheet())
        ->and(file_get_contents(base_path($cases)))->toBe(vignettesText());

    @unlink(base_path($sheet));
    @unlink(base_path($cases));
});
