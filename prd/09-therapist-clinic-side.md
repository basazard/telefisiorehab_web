# PRD 09: Therapist and Clinic Side, TeleFisioRehab

> **Status:** to build, in 4 slices · **Branches:** `feat/staff-s1` to `feat/staff-s4`, one pull request per slice
>
> **Status klinis:** nilai dan aturan klinis modul ini **disetujui 2026-10-26** (versi aturan `2026.1`, keputusan di `prd/clinical/decisions.csv`) oleh satu reviewer (Dokter Umum). **Review fisioterapis belum dilakukan** (PRD 10 §2 dan §8; keputusan pemilik, opsi C). Kata "DRAFT" di bawah menggambarkan status sebelum review.
> **Repository:** `basazard/telefisiorehab_web` · **Stack:** PHP 8.3.4 (no PHP 8.4+ syntax), Laravel 13.17, Livewire 3.6 + Volt, Pest 4, Chart.js 4 (already installed), Windows + PowerShell
> **Read first:** `CLAUDE.md`, `prd/03` (red flags), `05` (risk), `06` (program), `07` (monitoring). Do not change their clinical rules.

---

## 1. Why

The patient journey is built end to end. The staff side is not: `routes/web.php` registers `therapist.dashboard`, `therapist.patients`,
`therapist.patients.show`, `clinic.dashboard` and `clinic.team`, but none of the five pages exist. `Role::home()` sends therapists and clinic admins to
the first of them, so staff land on an error after login. Staff can only use *Red flags* and *Konsultasi*. Nobody can see a patient's progress, which is
the core of the product ("monitoring by physical therapists via a data-driven dashboard").

## 2. What already exists (verified in the repo, reuse it)

- `patients.therapist_id` exists but nothing sets or reads it (only `ScreeningPolicy`).
- `PatientPolicy` and `ProgramPolicy` are generated stubs (deny everything). `ScreeningPolicy`, `RedFlagCheckPolicy`, `ConsultationRequestPolicy` are real.
- Staff pages to copy the pattern from: `pages/therapist/red-flag-review.blade.php` and `consultation-requests.blade.php`
  (explicit `clinic_id` filter on top of the global scope, `Gate::authorize`, one note per row, Indonesian copy).
- Programs: `Program` (`status`: draft, active, needs_review, ended; `pathway`, `intensity_level`, `needs_monitoring`, `next_evaluation_on`, `end_reason`),
  `ProgramExercise` (sets, reps, hold_seconds), `ExerciseLog` (pain_score, effort, performed_at), `ProgramEvaluation` (comparison, tier, outcome, goal_achieved).
  `ProgramStatus::Draft` is unused. Programs are created by `Programs::build()` with `created_by = null`.
- Reusable rules: `Programs::redose`, `Programs::buildMaintenance`, `Program::end($reason)`, `Intervention::adherence`, `Intervention::MAX_LEVEL`, `ExerciseLibrary::sync()`.
- Navigation already lists the staff items (`app/Support/Navigation.php`).
- Factories exist for Clinic, Exercise, ExerciseLog, Patient, Program, User. Missing: Screening, AssessmentResult, ProgramEvaluation, ConsultationRequest, RedFlagCheck, ProgramExercise.
- `ClinicSeeder`, `PatientSeeder`, `ProgramSeeder` are empty stubs.

## 3. Working rules (read first)

1. One slice at a time, on its own branch. **Before coding a slice, show a short plan** (files, migrations, tests) and wait for a go-ahead.
2. Baseline first: run `php artisan test --compact` and note the counts. It must be green after every slice.
3. Authorization lives in **policies** (`PatientPolicy`, `ProgramPolicy`), never inline `if` checks in pages. Staff only see their own clinic
   (explicit `where('clinic_id', ...)` on top of the global scope); platform admin sees all. Never trust ids from the browser: re-check access on the server.
4. New tables or columns go in **new migrations**; never edit a migration that has run. Never delete rows that have logs (see S3).
5. UI copy is Indonesian. Reuse the existing components (`x-panel`, `x-page-header`, `x-stat`, `x-ui.*`). Responsive from phone width.
6. Rules go in pure classes under `app/Support/Staff/` with Pest tests; pages stay thin.
7. Every slice adds tests (policies, pages, rules). No change to patient flow behaviour except where this PRD says so.
8. Do not add new clinical thresholds silently. Any new number is labelled **DRAFT** in code and in the final report.
9. Report after each slice: files changed, migrations, test result vs baseline, decisions you took, open questions.

## 4. Who can do what

| Action | Therapist | Clinic admin | Platform admin |
| --- | --- | --- | --- |
| See the clinic's patient list | assigned patients + unassigned ones (limited columns) | all clinic patients | all |
| Open a patient's detail | only patients assigned to them | all clinic patients | all |
| Claim an unassigned patient | yes (own clinic) | n/a | n/a |
| Assign or reassign a therapist | no | yes (own clinic) | yes |
| Review, adjust, create or end a program | assigned patients | all clinic patients | all |
| Add staff notes | assigned patients | all clinic patients | all |
| Clinic dashboard and team | no | yes (own clinic) | no (out of scope) |

Every denied action returns 403 and is covered by a test. Patients never see staff notes.

## 5. Slice S1: demo data, therapist dashboard, patient list

**Goal:** a therapist can log in, see who needs attention, and see their patients. Demo data makes the rest buildable.

### S1.1 Demo seeder
- `DemoSeeder` (calls the three stub seeders, filled in). Run with `php artisan db:seed --class=DemoSeeder`. **Refuse to run in production** (`app()->isProduction()`). Idempotent (`updateOrCreate` by email) and deterministic (dates relative to `now()`).
- Add the missing factories (with states such as `low()`, `moderate()`, `high()`, `completed()`).
- Cast (password `password` for every demo account, demo only):
  - Platform admin `admin@demo.test`.
  - Clinic A "Klinik Sehat Jakarta": admin `admin.a@demo.test`; therapists `ani.a@demo.test`, `budi.a@demo.test`; 12 patients.
  - Clinic B "Klinik Bugar Bandung": admin `admin.b@demo.test`; therapist `citra.b@demo.test`; 3 patients (to prove isolation).
  - 2 patients without a clinic (one of them locked by a red flag).
- Clinic A patient states: new (profile incomplete); profile done, no screening; screening in progress; locked by red flag (chest pain);
  Rendah with an active self-management program and 14 days of good logs; Sedang guided program with moderate adherence and one improving evaluation;
  Sedang with adherence below 50% and a "same" evaluation; Sedang overdue for evaluation; program in `needs_review`; Tinggi with an open consultation request;
  Tinggi with a handled referral; goal achieved with a maintenance program; pediatric patient with no program; geriatric patient.
- Spread assignments: some to Ani, some to Budi, three unassigned. Logs cover the last 28 days with a believable pain trend.
- README gets a "Demo accounts" section.

### S1.2 Policies
Implement `PatientPolicy` (`viewAny`, `view`, `assign`, `claim`) per the matrix. Leave the unused stub methods denying.

### S1.3 Attention rules
`App\Support\Staff\Attention` (pure): given a patient's program, logs and evaluations, returns reasons, any of:
`open_consultation`, `red_flag_locked`, `needs_review`, `evaluation_overdue` (`next_evaluation_on` before today), `low_adherence`
(below 50 % over the last 7 days, the Monitoring PRD value), `worsening` (latest evaluation outcome worse), `no_activity` (**DRAFT**: no log for 5 days while a program is active).

### S1.4 Pages
- `pages/therapist/dashboard` (`therapist.dashboard`): stat cards (my patients, need attention, open consultations, evaluations due this week) and an
  "Perlu perhatian" list (top 10, each with its reasons and a link to the detail).
- `pages/therapist/patients` (`therapist.patients`): table with search by name, filters (risk tier, pathway, mine / unassigned / needs attention),
  columns: name, age and module, risk tier, pathway, adherence 7 days, last activity, next evaluation, flags, assigned therapist.
  Needs-attention rows first. Pagination of 15. Unassigned patients show only name, age, risk tier and an "Ambil pasien" button.
- Assignment: therapists claim an unassigned patient of their clinic; clinic admins assign or reassign through a dropdown limited to therapists of the same clinic.
- Clinic admins also use these pages (all clinic patients). `therapist.patients.show` stays missing until S2: link it only when it exists.

### S1 acceptance
- Therapist and clinic admin logins land on a working dashboard (no error page).
- A therapist never sees another clinic's patients; unassigned patients expose no detail beyond the limited columns.
- Claiming and assigning change `patients.therapist_id` with server-side checks (same clinic, role therapist).
- Attention rules and the policy matrix have unit tests; the list page has a test per filter.
- The seeder runs twice without duplicates and refuses to run in production.

## 6. Slice S2: patient detail with progress

**Goal:** a therapist can see how a patient is doing.

### S2.1 Audit log (needed before staff open health data)
New table `patient_access_logs` (`user_id`, `patient_id`, `action`, `created_at`; append only, no updates). Record `viewed_detail`,
`assigned`, `note_added` and every program change from S3. A small `Audit::record()` helper; no UI yet.

### S2.2 Page `therapist.patients.show` (route binding with `PatientPolicy::view`, 403 otherwise)
Header: name, age, gender, module, clinic, assigned therapist, phone and email, badges (red-flag locked, consultation open, needs review).
Tabs (`x-ui.tabs`):
1. **Ringkasan:** latest risk tier, pathway and domain, main complaints, body regions as chips, goals and special conditions, open consultation request.
2. **Progres:** weekly adherence (bar chart), pain trend from logs and evaluations (line chart), evaluation timeline (function, mobility, comparison, outcome),
   last 30 days of logs (exercise, sets and reps, pain, effort, note). Charts use Chart.js through Alpine; data comes from `App\Support\Staff\Progress` (pure, tested).
3. **Riwayat:** screenings and assessments with tier and date, red-flag stops with who unlocked them and the note.
4. **Catatan:** staff notes. New table `patient_notes` (`patient_id`, `clinic_id`, `user_id`, `body`, `created_at`), append only, newest first, clinic scoped, never shown to the patient.

### S2 acceptance
- Only authorised staff can open it (tests: assigned therapist yes, other therapist of the same clinic no, other clinic no, clinic admin yes, patient no).
- Every figure matches the seeded data (tests on `Progress`); empty states for patients without a program.
- Eager loading, no N+1 on the detail page; opening it writes one audit row.

## 7. Slice S3: program review, adjustment and creation

**Decision D2 (default):** "review, not gate". Sedang programs stay active at once (PRD 06). A therapist **reviews** them; the patient then sees "Disetujui fisioterapis".

### S3.1 Data (new migrations)
- `programs`: `reviewed_by` (nullable user), `reviewed_at`, `review_note`.
- `program_exercises`: `removed_at` (nullable). **Never delete an item that has logs** (the foreign key cascades and would erase the patient's history). Patient pages and
  adherence ignore removed items; progress still counts their old logs.

### S3.2 Program tab on the patient detail
Shows the current program (pathway, level, status, next evaluation, items with dose) and its history. Actions, all through `ProgramPolicy` and all audited:
- **Setujui program** (optional note): sets `reviewed_by/at/note`.
- **Sesuaikan:** change intensity level (1 to `Intervention::MAX_LEVEL`, then `Programs::redose`), edit an item's sets, reps or hold within sensible limits,
  remove an item (soft), add an exercise from the library (global plus the clinic's own). Any adjustment marks the program reviewed and needs a note of at least 10 characters.
- **Program `needs_review`:** choose *Lanjutkan dengan penyesuaian* (back to active, optional lower dose) or *Akhiri program* (`Program::end` with a reason, then the patient is told to reassess).
- **Tandai tujuan tercapai:** `Program::end('Tujuan fisioterapi tercapai')` then `Programs::buildMaintenance($previous)`. This replaces the deferred "goal achieved by therapist" item of PRD 07.
- **Buat program** for patients without one (pediatric first): pick exercises (filter by body area and type), set doses, title; creates an `active` program with `created_by` = the therapist,
  `pathway` guided, `needs_monitoring` true, evaluation every 2 weeks (**DRAFT**), already reviewed.

### S3.3 Safety rules (server side, tested)
- Staff cannot create a self-exercise program for a patient who is locked by a red flag or whose latest risk tier is Tinggi (open question D3).
- Dose edits stay inside the limits used by `Intervention::dose` (no values the system itself would never produce without a note and an upper bound).
- A program that is ended or removed never deletes logs, evaluations or history.

### S3.4 Patient side (minimal)
The program page shows the badge ("Disetujui fisioterapis" or "Disesuaikan fisioterapis") and the therapist's note. Nothing else changes for the patient.

### S3 acceptance
Policy tests (assigned therapist yes; others no), adherence unaffected by removed items, patient sees the badge, goal-achieved creates the maintenance program, creating a program for a pediatric patient works, Tinggi and locked patients are refused, every change writes an audit row.

## 8. Slice S4: clinic dashboard and team

### S4.1 Page `clinic.dashboard` (clinic admin)
Stat cards (active patients, new in 30 days, average adherence 7 days, programs needing review, open consultations, red-flag locks, overdue evaluations),
a doughnut of patients by risk tier, a therapist workload table (assigned patients, need attention, average adherence) and a list of **unassigned patients** with an assign dropdown.

### S4.2 Page `clinic.team`
List of staff in the clinic (name, email, role, status, number of patients). **Invite a therapist:** new table `staff_invitations`
(`clinic_id`, `email`, `role`, `token_hash`, `expires_at`, `invited_by`, `accepted_at`). The invitation page `/invitation/{token}` lets the person set a name and password and creates the user
(role therapist, `clinic_id` set). Send the link by email; if mail is not configured, show the link to copy so demos work. Invitations expire after 7 days (**DRAFT**), are single use, and the token is stored hashed.

### S4.3 Deactivate a therapist
New column `users.deactivated_at`. A deactivated user cannot log in or use the app (middleware check). Deactivating requires reassigning or releasing their patients
(show the count and a reassign dropdown). A clinic admin cannot deactivate themselves or another admin.

### S4.4 Optional, can move to a later "B2B" slice: clinic code for patients
`clinics.join_code` (unique, regenerable). The register page accepts `?clinic=CODE`; a valid code sets `clinic_id` on the new user and patient. Without it, new patients have no clinic and clinic dashboards stay empty outside the seeded demo.

### S4 acceptance
Clinic admin sees only their clinic's numbers (tests with two clinics); invitation is single use and expires; a deactivated user is locked out; reassigning on deactivate works; platform admin pages are unchanged.

## 9. Open decisions (defaults will be used if the owner does not answer)

| # | Question | Default used |
| --- | --- | --- |
| D1 | May therapists see unassigned patients of their clinic? | Yes, limited columns only; detail after claiming |
| D2 | Should a Sedang program wait for approval before the patient can start? | No: review after the fact |
| D3 | May a therapist prescribe a supervised program to a Tinggi patient? | No in this PRD |
| D4 | Build the clinic code for patients (S4.4) now? | Optional, last |
| D5 | The 5-day "no activity" alert and the 7-day invitation expiry | DRAFT values, clinic to confirm |
| D6 | Who can deactivate staff? | Clinic admin, for therapists of the own clinic |

## 10. Out of scope

Video consultation, push, email or WhatsApp reminders, billing and subscriptions, entering therapist-assessed instruments (ROM, MMT, BBS, Denver II and the others in PRD 04),
a clinic's own exercise-library editor, exports and reports, a timezone per user, Indonesian translation files, platform-admin dashboards.

## 11. Definition of done

- [ ] Each slice merged by pull request with tests green and CI green (if PRD 08 is done).
- [ ] Staff roles can use every page in the navigation; no route points to a missing page.
- [ ] Policy tests cover every cell of the permission matrix.
- [ ] No N+1 on list and detail pages; no patient data visible across clinics.
- [ ] `README` has the demo accounts; `CLAUDE.md` "Open items" is updated.
