# PRD 08: Hardening (foundation fixes), TeleFisioRehab

> **Status:** to build · **Branch:** `chore/hardening` · **Estimated effort:** half to one day
> **Repository:** `basazard/telefisiorehab_web` · **Stack:** PHP 8.3.4 (do not use PHP 8.4+ syntax), Laravel 13.17, Livewire 3.6 + Volt, Pest 4, Windows + PowerShell

---

## 1. Goal

Remove the traps that would hurt later work: wrong day boundaries for Indonesian users, misleading agent and README files,
leftover Breeze files, no automated test run, and no real email setup. No new product features.

## 2. Working rules (read first)

1. Work on branch `chore/hardening`. Do the tasks **one at a time, in order**. Commit after each task with a clear message
   (`chore: ...`, `docs: ...`, `ci: ...`).
2. Before the first change, run `php artisan test --compact` and write down the pass/fail counts (the baseline).
   After every task run it again. If a task makes more tests fail than the baseline, stop and report.
3. Show the planned change and ask for a go-ahead before tasks H5 and H6.
4. Never write secrets: `.env.example` gets placeholders only. Never commit `.env`.
5. Do not edit a migration that has already run. Never run `migrate:fresh` against a non-test database.
6. Delete only the files listed in this PRD. Ask before deleting anything else.
7. After each task answer with: files changed, test result vs baseline, and any decision the owner must take.

## 3. Tasks

### H1. Baseline
Run `php artisan test --compact` and report the counts. No code change.

**Done when:** counts are reported.

### H2. Timezone
**Problem:** `config/app.php` uses `'timezone' => 'UTC'`. Daily logic (`today()`, `whereDate('performed_at', today())` in
`pages/patient/dashboard` and `pages/patient/program`, monitoring due dates) rolls over at 07:00 WIB.

**Do:**
- In `config/app.php` use `'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta')`.
- Add `APP_TIMEZONE=Asia/Jakarta` to `.env.example` and `.env`.
- Fix tests that assume UTC (`tests/Unit/Flow/MonitoringFlowTest.php`, `InterventionFlowTest.php`): change the tests, not the app logic,
  and explain each change.
- Add a regression test: with the test clock at 2026-10-21 00:30 in Asia/Jakarta (2026-10-20 17:30 UTC), a log performed at
  2026-10-21 00:10 Asia/Jakarta counts as done today on the program page.

**Out of scope:** a timezone per user (later).

**Done when:** all tests pass, the new test passes, `php artisan tinker` shows `now()->timezoneName` = `Asia/Jakarta`.

### H3. Agent and project documents
- Replace `AGENTS.md` (it is the Laravel Boost bootstrap that tells agents to install PHP 8.5) with a short note: read `CLAUDE.md`;
  the project uses PHP 8.3; do not install PHP 8.5; Laravel Boost is not required.
- Replace `README.md` (still Laravel's default) with: what TeleFisioRehab is (home physiotherapy, patients, therapists, clinics),
  requirements (PHP 8.3, Composer, Node, MySQL), Windows setup steps (`composer install`, `.env`, `key:generate`, `migrate --seed`,
  `npm install`, `npm run dev`), how to run tests, a short folder map (`app/Support/Onboarding`, `resources/views/livewire/pages`, `tests/Unit`),
  and a link to `prd/`.
- The owner puts the seven PRD files from `README.zip` into `prd/`. Add this file as `prd/08-hardening.md` and link both from the README.
- Update the "Open items" section of `CLAUDE.md` to match the current state (remove what this PRD finishes).

**Done when:** an engineer can set up the project from the README alone.

### H4. Remove leftovers
Verified unused (no reference in `app`, `routes`, `resources`, `tests`). Delete:

- `resources/views/livewire/pages/auth/delete-user-form.blade.php`
- `resources/views/livewire/pages/auth/update-password-form.blade.php`
- `resources/views/livewire/pages/auth/update-profile-information-form.blade.php`
- `resources/views/livewire/pages/patient/red-flags.blade.php` (empty stub)
- `resources/views/livewire/welcome/navigation.blade.php`
- `resources/views/dashboard.blade.php`

**Keep** `resources/views/livewire/layout/navigation.blade.php`: Breeze's logout test in `tests/Feature/Auth/AuthenticationTest.php` uses it.

Also: remove the commented-out route blocks in `routes/web.php`; set `APP_NAME=TeleFisioRehab` in `.env.example`.

**Done when:** `php artisan view:clear` and the tests pass; no route or view references a deleted file.

### H5. Continuous integration (propose first)
Create `.github/workflows/tests.yml`:
- Triggers: `push` and `pull_request`.
- `ubuntu-latest`, PHP 8.3 via `shivammathur/setup-php` with extensions `mbstring, pdo_sqlite, sqlite3`.
- Steps: checkout, cache Composer, `composer install --no-interaction --prefer-dist`, copy `.env.example` to `.env`,
  `php artisan key:generate`, setup Node (use the version from `node -v` locally), `npm ci`, `npm run build`, `php artisan test --compact`.
- Tests use SQLite in memory (already set in `phpunit.xml`).

**Done when:** the workflow runs green on the pull request. If it fails only on Linux (for example file-name capitalisation), fix the cause and report it.

### H6. Email configuration (propose first, no secrets)
- Replace the `MAIL_*` lines in `.env.example` with SMTP placeholders: `MAIL_MAILER=smtp`, `MAIL_HOST=`, `MAIL_PORT=`, `MAIL_USERNAME=`,
  `MAIL_PASSWORD=`, `MAIL_FROM_ADDRESS=`, `MAIL_FROM_NAME="${APP_NAME}"`. Keep `MAIL_MAILER=array` in `phpunit.xml`.
- Add an "Email setup" section to the README: how to pick a provider, where to put the credentials (local `.env` only), and how to send a
  test message from the forgot-password page.

**Done when:** the README explains the setup and `.env.example` contains no real values.

### H7. Email verification (blocked: the owner must confirm first)
Start only after the owner says a real email arrived. Then make `App\Models\User` implement `MustVerifyEmail`, run the tests, and fix the ones that
assume a new user can reach the dashboard without verifying. Routes already use the `verified` middleware.

### H8. Spatie permission (report only)
Report where `spatie/laravel-permission` is used (`HasRoles` on `User`, `config/permission.php`, the permission tables migration). Roles currently
come from the `role` enum column. Change nothing; the owner decides whether to remove the package.

## 4. Out of scope

Therapist and clinic pages, billing, push or WhatsApp reminders, Indonesian translation files (`APP_LOCALE`), a timezone per user, code formatting
with Pint, and any clinical rule changes.

## 5. Definition of done

- [ ] H1 to H6 merged through a pull request; H7 and H8 done or explicitly deferred by the owner.
- [ ] Test count and pass rate are not worse than the baseline; CI is green.
- [ ] README and `AGENTS.md` are correct; no leftover file listed in H4 remains.
- [ ] No secrets in the repository.
