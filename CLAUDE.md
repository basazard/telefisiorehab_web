# TeleFisioRehab: notes for Claude Code

## What this is
Laravel app for home-based physiotherapy: patients follow exercise programs, therapists monitor progress, clinics are B2B partners
and patients can subscribe. It is a migration of a React/TanStack UI (https://github.com/basazard/telefisiorehab, read-only reference;
design tokens live in its `src/styles.css`, already ported to `resources/css/app.css`).

## Stack and environment
- Windows + PowerShell, PHP 8.3.4: **do not use PHP 8.4+ syntax** (for example the `|>` pipe operator).
- Laravel + Breeze (Livewire 3, Volt), Tailwind 4, Alpine, Pest. Icons: `mallardduck/blade-lucide-icons` (`<x-lucide-name />`).
- Blade components converted from shadcn: `x-ui.*`, `x-panel`, `x-page-header`, `x-onboarding.*`, `x-body-map`.
- UI copy is Indonesian. Tests live in `tests/Unit` (SQLite in memory; helpers in `tests/Helpers.php`).
- Run tests with `php artisan test`. Run `php artisan view:clear` after changing Blade files.

## Roles and access
- `App\Enums\Role`: patient, therapist, clinic_admin, platform_admin. Middleware aliases: `role`, `profile.complete`, `redflag.clear`.
- `BelongsToClinic` scopes data by clinic. Patients skip the scope, so in patient pages always query through the relation
  (`$patient->screenings()`), never `Screening::all()`.
- Route names: `patient.*`, `therapist.*`, `clinic.*`, `staff.red-flags` (staff + platform admin).

## Product flows (PRDs as built: `prd/`; original PRDs: `../../Flow Sriyani/md/`)
1. Profile wizard (`livewire/pages/patient/profile-setup`): date of birth (age is calculated), gender male/female, height/weight/BMI,
   occupation, activity hours, special conditions (pregnancy only for female), up to 3 goals.
2. Initial Screening (`livewire/pages/patient/screening`): complaints, body map (35 regions), characteristics, red flags, special groups.
3. Red-flag hard stop: any "yes" or pain >= 9 stops the program on the SERVER. Only staff of the same clinic (or a platform admin)
   can unlock, with a written note. Stop page: `pages/patient/red-flag-stop`; staff page: `pages/therapist/red-flag-review`.
4. Clinical Assessment (PRD 4, `Flow Sriyani/md/4_prd_modul_clinical_assessment_telefisiorehab.md`): `pages/patient/domain`
   picks 1 of 7 domains (`ClinicalAssessment::pickDomain`, server side, stored on `assessments`), `pages/patient/assessment` runs
   the self-report instruments (`Support\Onboarding\Instruments`), `assessment-result` shows the Ya/Tidak branch.
   Clinician-only tests (BBS, MMT, MAS, 6MWT, GMFM, Denver II/KPSP, MMSE, FMS) are listed, not filled yet.
   The legacy `Assessment::pickDomain/score` and `Questionnaires::TIERS/LEFS_*` are unused.
5. Scoring & Risk Stratification (PRD 5): runs on the server when the assessment is finished (`AssessmentResult::stratify()`,
   bands in `Support\Onboarding\RiskStratification`). Overall `tier` = highest band; "Sedang" starts at the Clinical
   Assessment cut-offs, so no limitation = Risiko Rendah. Risiko Tinggi warns and continues (`needsSupervision()`).
   Emergencies (SpO2 < 90 %, wound infection) become a red-flag stop on the screening's `red_flag_checks` row.
   Page: `pages/patient/risk-classification`.
6. Exercise / Telefisioterapi Recommendation (PRD 6): `pages/patient/intervention` routes by `tier` (Rendah = self management,
   Sedang = terbimbing + monitored, Tinggi = consultation/referral). `Support\Onboarding\Programs::build()` generates the
   program from `ExerciseLibrary` (seeded by slug) and REFUSES Risiko Tinggi and pediatric (safety block on the server).
   `program` = log latihan, `history`, `dashboard` = in-app reminders. Consultation/referral requests: `consultation_requests`, staff page
   `staff.consultations`. A Risiko Tinggi reassessment ends the running program. No push/email reminders, no video call yet.
7. Monitoring & Reassessment (PRD 7): `pages/patient/evaluation` (route `patient.evaluation`) re-runs the baseline
   assessment's instruments (shared `x-instrument-fields`) + NRS, global change, new complaint, side effects, barriers.
   `Support\Onboarding\Monitoring` compares with the baseline (MCID per instrument) and decides: progress (level + 1 and
   a variation), modify (adherence first, else lighter dose), reassess (worse, or 4+ weeks without improvement: paused),
   referral (drastic, emergencies escalate to a red-flag stop), maintenance (goal: Risiko Rendah, NRS <= 2, nothing worse:
   program ends, `Programs::buildMaintenance()`). Photo/video uploads are skipped for now.

## Rules
- Livewire/Volt pages keep their PHP block (layout, logic) at the top and the markup below the closing `?>`.
  Never overwrite a page file with a markup-only template. Auth pages must keep `#[Layout('layouts.guest')]`.
- Safety decisions (red flags) are never taken from browser input.
- Ask before deleting files or editing a migration that has already run. Never run `migrate:fresh` against a non-test database.
- This is health data: keep access checks (policies, clinic scope) and tests when changing related code.

## Open items
- Hardening (`prd/08-hardening.md`): H1–H7 done (app timezone is now `Asia/Jakarta` via `APP_TIMEZONE`; CI in
  `.github/workflows/tests.yml`; email setup in README; `User` implements `MustVerifyEmail`, so every page behind the
  `verified` middleware needs a confirmed email; the factory creates verified users). H8 is a report only.
- `PageSmokeTest` "renders every converted UI component" fails (x-ui.dialog inline `x-slot:trigger` renders `@endslot` as
  text). It is skipped on CI (`CI` env set) and still fails locally until fixed.
- Red-flag gaps vs PRD 3 (`prd/03-red-flag-screening.md`): "gangguan penglihatan mendadak" is missing, sudden numbness only
  covers the saddle area, the PRD 3 disclaimer lines are not shown. Pregnancy has no screening or domain of its own.
- Red-flag questions and thresholds need clinical sign-off before launch. So do the domain mapping, the instrument
  cut-offs and the education text of the Clinical Assessment (all DRAFT, in `ClinicalAssessment` and `Instruments`),
  and the risk bands and emergency rules (DRAFT, in `RiskStratification`), and the exercise library, doses, education
  (DRAFT, in `ExerciseLibrary` and `Intervention`), and the MCIDs, reassessment period and goal rule (DRAFT, in `Monitoring`).
- Licences to check before launch: ODI, SPADI, Borg CR10.
- Exercise videos: `exercises.video_url` is empty; the program page shows a video link once it is filled.
- Staff side (`prd/09-therapist-clinic-side.md`): S1 to S4 done (S4.4 clinic join code for patients deferred). `therapist.dashboard`, `therapist.patients` and
  `therapist.patients.show` exist (rules in `App\Support\Staff\Attention` and `Progress`, rows in `PatientOverview`,
  access in `PatientPolicy`; demo data: `DemoSeeder`). Every denied staff action is a 403, also for a patient of
  another clinic (owner's decision): load a patient id from the browser without the clinic scope and let the policy
  answer. Staff access is audited with `Audit::record()` (append-only
  `patient_access_logs`); staff notes are `Patient::staffNotes()` (`patients.notes` is an old, unused column with the
  same name, do not confuse them). Charts: Alpine `chart` component in `resources/js/app.js`.
  S3 done: the "Program" tab (`livewire/staff/program-panel`) approves, adjusts, resumes, ends, marks the goal achieved
  and creates programs through `App\Support\Staff\ProgramActions` (rules in `ProgramRules`, access in `ProgramPolicy`).
  Items are never deleted: `program_exercises.removed_at`, and `Program::items()` skips removed ones (`allItems()` keeps
  them). `programs.review_type` drives the patient badge; `programs.ended_by` = ended by staff (patient is asked to
  reassess). S4: `clinic.dashboard` (figures in `ClinicStats`) and `clinic.team` (access in `ClinicPolicy`,
  `UserPolicy`). Assigning goes through `App\Support\Staff\Assignment` (claim, assign, release; audited).
  Invitations: `Invitations` (only the SHA-256 of the token is stored, single use; with mail `log`/`array` the team
  page shows the link to copy), accepted at `/invitation/{token}`. `users.deactivated_at`: the `EnsureActive`
  middleware (web group) and `LoginForm` lock deactivated users out; deactivating moves or releases their patients.
  DRAFT: `Attention::NO_ACTIVITY_DAYS` (5), `ProgramRules::HARD_MAX` (sets 5, reps 20, hold 60 s, minutes 45),
  `CREATED_EVALUATION_WEEKS` (2) and `Invitations::EXPIRES_DAYS` (7).
