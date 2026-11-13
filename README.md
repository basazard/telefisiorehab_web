# TeleFisioRehab

Home-based physiotherapy web app. **Patients** complete a health profile and screening, get a physiotherapy domain and a
risk category, and follow a personalised exercise program with regular monitoring. **Therapists** handle red-flag stops and
consultation requests. **Clinics** are B2B partners whose staff only see their own patients.

The UI is in Indonesian. Product requirements, as built, are in [prd/](prd/README.md).

## Requirements

- PHP **8.3** (do not use 8.4+ syntax) with the `mbstring`, `pdo_mysql`, `pdo_sqlite`, `sqlite3` and `fileinfo` extensions
- Composer 2
- Node.js 24 and npm
- MySQL 8 (or MariaDB) for local development; tests use SQLite in memory

## Setup (Windows, PowerShell)

```powershell
git clone https://github.com/basazard/telefisiorehab_web.git
cd telefisiorehab_web

composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create an empty MySQL database (default name `telefisiorehab`), then set `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.

```powershell
php artisan migrate --seed      # tables + the built-in exercise library
npm install
```

Run the app in two terminals:

```powershell
php artisan serve               # http://localhost:8000
npm run dev                     # Vite (CSS/JS with hot reload)
```

Open http://localhost:8000/register and create an account. New accounts are patients: they first confirm their email
through the link that is sent to them (see [Email setup](#email-setup)), then start with the profile wizard.

## Demo accounts

For trying the staff side, load the demo data (never in production, the seeder refuses):

```powershell
php artisan db:seed --class=DemoSeeder
```

It can run again without making duplicates. Every demo account uses the password `password`.

| Account | Role |
| :-- | :-- |
| `admin@demo.test` | Platform admin |
| `admin.a@demo.test` | Clinic admin, Klinik Sehat Jakarta |
| `ani.a@demo.test`, `budi.a@demo.test` | Therapists, Klinik Sehat Jakarta |
| `admin.b@demo.test` | Clinic admin, Klinik Bugar Bandung |
| `citra.b@demo.test` | Therapist, Klinik Bugar Bandung |
| `pasien01.a@demo.test` … `pasien14.a@demo.test` | Patients of Klinik Sehat Jakarta, one per state of the patient journey (see `database/seeders/PatientSeeder.php`) |
| `pasien01.b@demo.test` … `pasien03.b@demo.test` | Patients of Klinik Bugar Bandung |
| `mandiri01@demo.test`, `mandiri02@demo.test` | Patients without a clinic |

Dates are relative to the day of the first run. Klinik Sehat Jakarta also has a pending invitation for
`dimas.a@demo.test`; to try the invitation flow, invite another address from the Team page (with mail set to `log`,
the page shows the link to copy).

Other useful settings in `.env`: `APP_TIMEZONE` (default `Asia/Jakarta`), `APP_URL`.

## Email setup

The app sends email for account verification (required before using the app), password reset and staff invitations.
Emails are in Indonesian and never contain a patient name or clinical details. Mail is sent synchronously: there is no
queue worker on Hostinger (`QUEUE_CONNECTION=sync`). If sending fails, the action still completes and one line
`mail.failed` (user id and error class only) is written to the log.

- **Locally:** keep `MAIL_MAILER=log`; emails are written to `storage/logs/laravel.log`.
- **On a server (staging, pilot):** use the Hostinger mailbox, in that server's `.env` only (never commit credentials):
  `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.hostinger.com`, `MAIL_PORT=465`, `MAIL_USERNAME` = the full mailbox address,
  `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` = the same address. Leave `MAIL_SCHEME` empty: Laravel uses SSL on 465 and STARTTLS
  on 587. This Laravel version ignores `MAIL_ENCRYPTION`.
- **Staging only:** set `MAIL_ALWAYS_TO` to your own address; every email then goes only there (the original recipients
  are in the `X-Original-To` header). Never set it on the pilot site.
- After changing `.env` on a server run `php artisan config:cache`, then `php artisan mail:test you@example.com`. It sends a
  test message, prints the settings it used (never the password) and records the time of the last success in
  `storage/app/last-mail-test`.

Tests always use `MAIL_MAILER=array` (see `phpunit.xml`), so they never send real email.

## Releases and deploys

Releases are built on your PC and deployed over SSH with the scripts in [scripts/](scripts/README.md):
`scripts/build-release.ps1`, then `bash scripts/deploy.sh staging release.tar.gz` (and later `production`), then
`bash scripts/smoke.sh <site> --noindex`. `scripts/README.md` (Indonesian) explains every check and how to set up staging.

## First accounts on a server

Never seed a pilot or production database. Create the accounts with commands; each sends a set-password email (no
password is ever printed), and running one again changes nothing (`--resend` sends a fresh link):

```bash
php artisan pilot:create-admin --name="Full Name" --email=you@example.com
php artisan pilot:create-clinic "Klinik Sehat Jakarta" --admin-name="Full Name" --admin-email=admin@example.com
```

`pilot:create-clinic` prints the clinic code and the patient register link (`/register?clinic=CODE`). With
`REGISTRATION_MODE=clinic_code` (the default) patients need that code to register; `closed` allows invitations only.
The clinic admin sees the code on the Team page and can make a new one there.

## Tests

```powershell
php artisan test --compact
```

Tests use SQLite in memory (see `phpunit.xml`), so they never touch your MySQL database. After changing Blade files, run
`php artisan view:clear`.

## Folder map

| Path | What is there |
| :-- | :-- |
| `app/Support/Onboarding` | Clinical rules: screening, red flags, domain picking, instruments, risk bands, exercise library, monitoring |
| `app/Models`, `app/Enums`, `app/Policies` | Data model, enums (roles, domains, risk tiers), access rules |
| `resources/views/livewire/pages` | Pages (Livewire Volt): `patient/*`, `therapist/*`, `auth/*` |
| `resources/views/components` | Shared Blade components (`x-ui.*`, `x-panel`, `x-instrument-fields`, …) |
| `routes/web.php` | Routes and middleware (`role`, `profile.complete`, `redflag.clear`) |
| `tests/Unit` | Pest tests: `Logic` (pure rules) and `Flow` (pages and access); helpers in `tests/Helpers.php` |
| `prd/` | Product requirements per module |

## Documents

- [prd/README.md](prd/README.md): index of the patient-flow PRDs (profile to monitoring and maintenance), with the rules that
  still need clinical sign-off
- [prd/08-hardening.md](prd/08-hardening.md): foundation fixes in progress
- [CLAUDE.md](CLAUDE.md) and [AGENTS.md](AGENTS.md): notes for coding agents
