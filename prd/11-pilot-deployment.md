# PRD 11: Pilot deployment (staging and real mail for one clinic), TeleFisioRehab

> **Status:** to build (engineering) + owner tasks in parallel · **Branch:** `main` (no feature branches); tags `pilot-eN-done` and `pilot-ready`
> **Repository:** `basazard/telefisiorehab_web` · **Stack:** PHP 8.3 (no PHP 8.4+ syntax), Laravel 13, Livewire 3 + Volt, Pest 4
> **Hosting:** Hostinger web hosting with SSH (PHP 8.3.35, MySQL). **No `proc_open`, no queue worker, cron only.** Deploys use `build-release.ps1` on the owner's PC and `deploy.sh` on the server.
> **Read first:** `CLAUDE.md`, `prd/08` (hardening), `prd/09` (staff side), `prd/10` (clinical sign-off).

---

## 1. Goal

One real clinic (about 1 clinic admin, 2 to 3 therapists, 10 to 20 patients) can use the app for 4 to 8 weeks with: real email, a staging copy to rehearse every release,
backups that have been restored once, alerts when something breaks, a consent screen, no demo data, and a command that refuses go-live while a safety item is open.

## 2. Where the project stands (observed in the repo, verify in E0)

- Built and in `main`: the patient journey, the staff pages (therapist dashboard, patients, patient detail, consultations, red flags; clinic dashboard and team), staff invitations,
  audit log, program review, email verification on `User`, timezone `Asia/Jakarta` in `.env.example`, a CI workflow (`.github/workflows/tests.yml`), the clinical rule set 2026.1 signed off, `php artisan clinical:gate`
  wired into `deploy.sh`, about 496 tests. The ODI, SPADI and Borg licences are recorded as `granted`.
- Not there yet: a way to link a patient to a clinic at registration (no `join_code`), Indonesian translations (`lang/` is missing), custom error pages, security headers
  (`public/robots.txt` allows indexing), consent and privacy pages, backups, error alerts, a staging environment, build and backup scripts in the repo (`deploy.sh` is in the root, `build-release.ps1` is not committed),
  `LOG_LEVEL=debug` and `LOG_CHANNEL=stack` in `.env.example`, `APP_LOCALE=en`.
- Email verification is on, so **a broken mailbox blocks every new account**. Mail is the first thing to prove.

## 3. Working rules (read first)

1. Work directly on `main`. Commit small and often; run `php artisan test --compact` before every commit and never commit a failing test; push only when green; never force-push; tag milestones (`pilot-e1-done` and so on).
2. **Before each work package show a short plan and wait for the owner's OK.** Do the packages in the order of section 5.
3. Claude Code cannot reach the Hostinger server. It writes code, scripts and runbooks; **the owner runs server commands** and reports the output.
4. No secrets in the repository or in chat; `.env.example` has placeholders only. No personal data or clinical details in logs, emails, alerts or the help page.
5. Runtime code must work **without `proc_open`** (Hostinger disables it): no `Process::run`, no `exec` inside the app. Backups run from shell scripts and cron, not from Laravel.
6. Never run `db:seed`, `DemoSeeder` or `migrate:fresh` on the pilot database. Demo data is allowed only on `local` and `staging`.
7. Do not change clinical values or texts. New numbers in this PRD are labelled **DRAFT** in code and in the final report.
8. Report after each package: files changed, tests vs baseline, what the owner must do next, anything unknown.

## 4. Environments (decision D1)

| Environment | Domain | Data | Mail | Purpose |
| --- | --- | --- | --- | --- |
| local | the owner's PC | demo | `log` | development |
| **staging** (new) | `staging.telefisiorehab.com` | demo only, never real patients | all mail redirected to the owner (`MAIL_ALWAYS_TO`) | rehearse every release before the clinic sees it |
| **pilot** | `telefisiorehab.com` (the existing site) | real clinic data | real | the clinic's trial |

Both hosted environments are `noindex`, run `APP_DEBUG=false`, and have their own database, `.env` and app folder. Staging shows a red "STAGING" banner.

## 5. Work packages

### E0. Readiness audit (report only, no code)
Run the tests for a baseline. Check every item in section 2 and `prd/09`, `prd/10` against the code and report: done, missing, or different. Specifically confirm: clinic code (S4.4) missing, `MustVerifyEmail` active,
`clinical:gate` and the licence register status, what `deploy.sh` and CI do, what `/up` returns, and that `DatabaseSeeder` seeds only the exercise library. List anything that blocks the pilot.
**Done when:** the report is delivered and the owner agrees on the order.

### E1. Real mail (P0)
- **Owner:** create the sending mailbox in hPanel (for example `no-reply@telefisiorehab.com`); confirm the domain's SPF, DKIM and DMARC records in DNS; open the mailbox's "View limits" and write the daily limit down
  (Hostinger says limits depend on the plan and can change; one third-party review reports 100 per day on the free bundled mailbox, so verify). SMTP: host `smtp.hostinger.com`, port 465 (SSL) or 587 (STARTTLS), username is the full mailbox address.
  If the limit is too low for the pilot (D2), use a transactional provider instead.
- **Claude Code:**
  - `.env.example`: Hostinger placeholders and a comment with both port options (confirm the Laravel 13 `MAIL_SCHEME` values for 465 and 587 in the docs).
  - `php artisan mail:test {address}`: sends a plain message, prints the result and writes the time of the last success to `storage/app/last-mail-test` (used by E10).
  - Indonesian email: `APP_LOCALE=id`, `APP_FALLBACK_LOCALE=en`, `lang/id` for auth, validation, passwords and pagination; Indonesian subjects and bodies for verification and password reset; publish and brand the mail layout (name, colour, footer with the non-diagnostic note). Tests assert the Indonesian text.
  - Rule and test: **no email contains clinical details or a patient's name.**
  - Sending is synchronous (no worker). A mail failure must never break a patient or staff action: catch, log without personal data, continue.
- **Acceptance:** verification and reset emails reach a Gmail and an Outlook inbox (not spam) within a minute; the owner's mail-tester score is 8 or higher (DRAFT target).

### E2. Pilot hardening (P0)
- `.env.example` keys: `PILOT_MODE`, `REGISTRATION_MODE` (`open`, `clinic_code`, `closed`; default `clinic_code`), `MAIL_ALWAYS_TO`, `ALERT_EMAIL`, `HEALTH_TOKEN`, `SUPPORT_EMAIL`, `SUPPORT_WHATSAPP`.
- Production and staging: force HTTPS URLs; `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_LIFETIME=60` (DRAFT); `LOG_CHANNEL=daily`, `LOG_DAILY_DAYS=14`, `LOG_LEVEL=warning`.
- A middleware for security headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` with camera and microphone off, `Strict-Transport-Security`
  (only when `APP_URL` is https), and `X-Robots-Tag: noindex, nofollow` when `PILOT_MODE` or staging. `public/robots.txt` served as `Disallow: /` in those cases. A Content-Security-Policy is out of scope (Livewire and Alpine need nonces).
- Throttling: confirm the login limiter and add throttles to password reset, resend verification and consultation requests, with tests.
- Error pages in Indonesian for 403, 404, 419 (session expired), 429, 500 and 503 (with the retry time), branded, with no stack traces.
- Logging must not contain passwords, request bodies or health data: test an exception thrown from a form with sensitive input.
- Seeders: `DemoSeeder` allowed only on `local` and `staging`; a test proves it refuses in production.
**Done when:** the tests pass and `curl -I` on staging shows the headers.

### E3. Clinic code and first accounts (P0)
- **Clinic code (PRD 09 S4.4):** `clinics.join_code` (unique, regenerable by the clinic admin on the team page). `REGISTRATION_MODE=clinic_code` requires a valid code at registration and sets `clinic_id` on the user and the patient;
  `closed` allows only invitations; `open` keeps today's behaviour. The landing page and register page adapt (no "Daftar" button when closed). A link `/register?clinic=CODE` fills the code.
- **First accounts without seeding:** `php artisan pilot:create-admin` (platform admin) and `php artisan pilot:create-clinic "Nama Klinik" --admin-name=... --admin-email=...` (clinic plus clinic admin). Both are idempotent, send a **set-password email** (so they also prove the mail),
  never print or log passwords, and work without `proc_open`.
**Acceptance:** on staging, the commands create the accounts, the email arrives, the admin sets a password and sees the clinic dashboard; a patient registers with the code and appears in the therapist's list.

### E4. Consent and privacy pages (P0, text from the owner and a lawyer)
- Public pages `/privasi` and `/syarat` rendered from `resources/legal/privasi-id.md` and `resources/legal/syarat-id.md`. The files start with the marker `TEKS SEMENTARA: belum ditinjau pengacara` until the owner replaces the text.
- Table `consents` (`user_id`, `version`, `accepted_at`) and `config('legal.consent_version')`. Middleware: after login, patients and staff must accept the current version before any other page (except logout, privacy and terms).
  A new version asks again. Separate required items: terms and privacy, and explicit consent to process health data; staff get a confidentiality statement. Items are defined in config so a lawyer's wording changes text, not code.
- A "Permintaan data saya" section on the profile page with the support email and what will happen (manual process in the pilot; tooling is out of scope).
**Acceptance:** tests for the gate and the re-prompt; `pilot:check` (E10) fails while the placeholder marker is present.

### E5. Backups and restore drill (P0)
- `scripts/backup-db.sh`: reads the database settings from `.env`; **no password on the command line** (a temporary `--defaults-extra-file` with mode 600); `mysqldump --single-transaction --no-tablespaces`, gzip, optional `gpg --symmetric` when `BACKUP_PASSPHRASE_FILE` is set;
  writes to `~/backups/telefisiorehab/db-YYYYmmdd-HHMM.sql.gz[.gpg]` outside the web root; keeps 14 daily and 8 weekly files (DRAFT); exits non-zero and writes one log line on failure.
- `scripts/restore-drill.sh`: restores the newest backup into a **scratch database**, compares row counts of the main tables and prints OK or FAIL; writes the time of the last success to `~/backups/last-restore-drill`.
- **Owner:** create a daily cron job in hPanel for the backup script (check the server timezone), download the newest encrypted backup weekly to a place off the server (D7), run the restore drill before go-live and then monthly, and note what Hostinger's own backups cover (not relied upon).
**Acceptance:** a backup file is produced by cron and the drill passes on a scratch database.

### E6. Monitoring and alerts (P0)
- `/health` (requires the `HEALTH_TOKEN` header): checks the database, cache, storage write, mail configuration present, newest backup age and the app revision; returns JSON with no secrets. Keep `/up` as it is.
- `deploy.sh` writes the commit short hash to `storage/app/revision.txt`; the app shows "v<hash>" in the footer of staff pages and in alerts.
- Error alert email: on an unhandled exception send **one** email per exception signature per 15 minutes (DRAFT) to `ALERT_EMAIL` with: time, environment, revision, method, URL path without query string, user id, exception class and a truncated message.
  Never request input, names, emails or health data. A mail failure is swallowed.
- **Owner:** register an external uptime monitor on `/up` every 5 minutes with alerts to the owner.
**Acceptance:** a forced exception on staging sends one alert and a repeat within 15 minutes sends none; a test proves the alert body has no personal data.

### E7. Staging environment and scripts in the repo (P0)
- **Owner:** add the subdomain in hPanel, create a separate MySQL database and user, create the app folder, set SSH access, create the staging `.env` (`APP_ENV=staging`, `MAIL_ALWAYS_TO` set, `PILOT_MODE=true`, `REGISTRATION_MODE=clinic_code`), then run the demo seeder there.
- **Claude Code:** move `build-release.ps1` and `deploy.sh` into `scripts/` with a short README each; make `deploy.sh` environment aware (accepts `production` and `staging`, prints the environment name and revision before it starts, runs `pilot:check` in production);
  add `scripts/smoke.sh <url>` that checks `/`, `/login`, `/register`, `/privasi`, `/syarat`, `/up` for HTTP 200 and the security headers; add `MAIL_ALWAYS_TO` support (all outgoing mail goes to that address when set).
**Acceptance:** one release is deployed to staging with the same scripts, `smoke.sh` is green, and every email sent on staging lands in the owner's inbox only.

### E8. Staff email alerts (P1)
When a patient is stopped by a red flag or a consultation request is created, email the clinic admin and the assigned therapist (all therapists of the clinic if unassigned): subject "Ada pasien yang perlu ditinjau", **no patient name and no clinical details**, a login link, and a count of pending items.
At most one email per recipient per 30 minutes (DRAFT). Failures never block the patient flow. Tests for who receives what and for the throttle.

### E9. Pilot banner, help and feedback (P1)
A slim "Uji coba (pilot)" ribbon when `PILOT_MODE=true`, linking to `/bantuan`: support email and WhatsApp number from `.env`, support hours, and "Bukan untuk keadaan darurat: hubungi 112 atau 119". A "Kirim masukan" link opens an email prefilled with the page address and the app revision (no personal data).
Staging shows a red "STAGING" ribbon instead.

### E10. Go-live gate: `php artisan pilot:check` (P0)
A table of PASS, WARN and FAIL; exit code 1 on any FAIL. `deploy.sh` runs it in production when `PILOT_MODE=true`. An override like `ALLOW_PILOT_FAIL=1` is allowed only for staging and is logged to `storage/logs/deploy-overrides.log`.
Checks: `APP_ENV=production`, `APP_DEBUG=false`, https `APP_URL`; timezone; mail driver and from address set and `mail:test` succeeded in the last 7 days; newest backup under 26 hours old and a restore drill within 35 days; `clinical:gate` passes;
the instruments ODI, SPADI and Borg are `granted` or switched off; the consent text has no placeholder marker; no demo or test accounts (emails ending `@demo.test` or `test@example.com`); `REGISTRATION_MODE` is not `open`;
at least one platform admin and one clinic admin exist; `revision.txt` exists; `HEALTH_TOKEN` and `ALERT_EMAIL` set.

### E11. Runbook, checklists and metrics (P2)
- `prd/pilot/pilot-runbook.md` (Indonesian, the owner edits): roles and contacts, how to onboard the clinic (create the clinic, admin, therapists, clinic code, patient handout), a 45-minute training agenda, support hours and response times,
  the incident process (what to do if a red-flag patient is not handled, a data incident, an outage), rollback (use `git revert` plus a redeploy, never reset `main`), a weekly check-in, and an end-of-pilot checklist (return or delete the data, feedback interview).
- `prd/pilot/smoke-checklist.md`: the manual path to walk on staging before every release (register with the code, verify email, profile, screening with a red-flag stop, staff email alert, unlock, assessment, program, log an exercise, therapist sees progress, password reset, logout, backup ran).
- `php artisan pilot:metrics`: aggregate counts only, no personal data: patients registered, profile and screening completion rates, red-flag stops, average adherence over 7 days, consultation requests and how many were handled, error alerts per week.

## 6. Owner tasks (parallel, people work)

| Task | Needed for |
| --- | --- |
| Create the mailbox, check DNS (SPF, DKIM, DMARC), write down the daily limit | E1 |
| Add the staging subdomain, database, SSH, `.env` | E7 |
| Set cron jobs: daily backup (and later any scheduler) | E5 |
| Set the external uptime monitor and the alert email | E6 |
| Get the privacy policy, terms and consent wording reviewed by a lawyer | E4 |
| Sign a short pilot agreement with the clinic (roles, data responsibilities, duration, return or deletion of data, support) | go-live |
| Agree the pilot goals and the metrics with the clinic; schedule the training | E11 |
| Decide the support contact and hours | E9 |

## 7. Open decisions (the defaults are used if the owner does not answer)

| # | Question | Default |
| --- | --- | --- |
| D1 | Separate staging subdomain? | Yes |
| D2 | Mail provider | Hostinger mailbox if its daily limit covers the pilot (expected well under 100 per day), else a transactional provider |
| D3 | Registration mode in the pilot | `clinic_code` |
| D4 | Staff session idle time | 60 minutes (DRAFT) |
| D5 | Alert throttle and staff email throttle | 15 minutes and 30 minutes (DRAFT) |
| D6 | Backup retention | 14 daily and 8 weekly (DRAFT), at most 24 hours of data lost |
| D7 | Off-server copy | manual weekly download of the encrypted file |
| D8 | Pilot size and length | 1 clinic, 10 to 20 patients, 4 to 8 weeks |
| D9 | Who answers support and when | the owner, working hours |

## 8. Out of scope

Video consultation, WhatsApp or push notifications, billing, a timezone per user, data export and deletion tools, a Content-Security-Policy, 2FA, a penetration test (do one before growing beyond the pilot), load testing, self-service clinic onboarding.

## 9. Definition of done (go-live checklist)

- [ ] E1 to E7 and E10 done, each tagged; tests and CI green; staging rehearsal of the exact release passed `smoke.sh` and the manual checklist.
- [ ] `php artisan pilot:check` is all PASS on the pilot site.
- [ ] A real verification email and a real password reset email arrived in the owner's inbox in Indonesian.
- [ ] A backup exists and a restore drill passed on a scratch database.
- [ ] The consent and privacy text is approved (no placeholder marker) and the clinic has signed the pilot agreement.
- [ ] No demo accounts exist on the pilot database; the first admin set a strong password.
- [ ] The owner received a test error alert and a test uptime alert.
