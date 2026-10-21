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

Open http://localhost:8000/register and create an account. New accounts are patients and start with the profile wizard.

Other useful settings in `.env`: `APP_TIMEZONE` (default `Asia/Jakarta`), `APP_URL`.

## Email setup

The app sends email for password reset ("Lupa kata sandi"). `.env.example` only has empty SMTP placeholders.

1. Pick a provider: a transactional email service (for example Mailgun, Postmark, Brevo or Amazon SES) or the SMTP server
   of your mail host. Get the SMTP host, port, username and password, and a sender address the provider allows.
2. Fill `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` and `MAIL_FROM_ADDRESS` in your local `.env` only.
   Never commit `.env` or put credentials in `.env.example`.
3. Run `php artisan config:clear`.
4. Test it: register an account with an inbox you can read, log out, open `/forgot-password`, enter that email and check
   that the reset email arrives (also look in the spam folder).

While developing without a provider, set `MAIL_MAILER=log`: emails are written to `storage/logs/laravel.log` instead of
being sent. Tests always use `MAIL_MAILER=array` (see `phpunit.xml`), so they never send real email.

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
