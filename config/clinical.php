<?php

/*
 | Clinical rules (PRD 10). The values themselves live in the code and in prd/clinical/parameters.json; this file only
 | holds the release gate and the version of the rule set.
 */

$register = env('CLINICAL_REGISTER_PATH', base_path('prd/clinical/parameters.json'));

// A switch that is not set (or left empty) stays on.
$on = fn (string $key): bool => in_array(env($key), [null, ''], true) ? true : filter_var(env($key), FILTER_VALIDATE_BOOL);

return [

    // Register file (tests point it elsewhere).
    'register_path' => $register,

    // Version of the clinical rule set, e.g. "2026.1-draft" before the sign-off and "2026.1" after it.
    // Read from the register so there is one source of truth; stored on each new assessment and program.
    'version' => is_file($register) ? (json_decode(file_get_contents($register), true)['clinical_version'] ?? 'unknown') : 'unknown',

    // The deploy gate (deploy.sh, `php artisan clinical:gate`) refuses unreviewed rules when true.
    // Default: required in production. Set CLINICAL_SIGNOFF_REQUIRED=false only for a non-production server.
    // An empty value counts as "not set", so a blank line in .env never switches the gate off in production.
    'signoff_required' => in_array(env('CLINICAL_SIGNOFF_REQUIRED'), [null, ''], true)
        ? env('APP_ENV') === 'production'
        : filter_var(env('CLINICAL_SIGNOFF_REQUIRED'), FILTER_VALIDATE_BOOL),

    // Licensed instruments (PRD 10 W6, prd/legal/instrument-licences.md). Switch one off when its licence is refused:
    // ODI / SPADI off: PSFS is used for that area. Borg off: the sit-to-stand is judged on repetitions only.
    'instruments' => [
        'odi' => ['enabled' => $on('CLINICAL_ODI_ENABLED')],
        'spadi' => ['enabled' => $on('CLINICAL_SPADI_ENABLED')],
        'borg' => ['enabled' => $on('CLINICAL_BORG_ENABLED')],
    ],

];
