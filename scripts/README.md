# Skrip rilis dan deploy (PRD 11 E7)

| Skrip | Dijalankan di | Gunanya |
| --- | --- | --- |
| `build-release.ps1` | PC Anda (PowerShell) | Membuat `release.tar.gz` (vendor, aset, dan commit yang dibangun) dari `main` terbaru di GitHub |
| `deploy.sh` | Server (SSH), di folder aplikasi | Memeriksa rilis, lalu memasangnya di staging atau production |
| `smoke.sh` | PC (Git Bash) atau server | Memeriksa situs yang sudah terpasang: halaman publik dan header keamanan |

## Alur setiap rilis

1. Pastikan semua perubahan sudah di-push ke `main` dan CI hijau.
2. Di PC: `.\scripts\build-release.ps1` membuat `release.tar.gz` di Desktop dan mencetak commit-nya.
3. Unggah `release.tar.gz` ke folder aplikasi **staging** di server, lalu lewat SSH:
   `bash scripts/deploy.sh staging release.tar.gz`
4. Dari PC: `bash scripts/smoke.sh https://staging.telefisiorehab.com --noindex`, lalu jalankan daftar uji manual
   (`prd/pilot/smoke-checklist.md`, E11).
5. Bila staging lolos, unggah **arsip yang sama** ke folder aplikasi production dan jalankan
   `bash scripts/deploy.sh production release.tar.gz`, lalu `bash scripts/smoke.sh https://telefisiorehab.com --noindex`.

Jangan push ke `main` di antara langkah 2 dan 5: `deploy.sh` menolak arsip yang tidak dibangun dari commit `origin/main`
saat itu. Bila `main` sudah berubah, bangun ulang arsipnya.

## Apa yang diperiksa `deploy.sh` sebelum situs masuk mode pemeliharaan

Bila salah satu gagal, deploy berhenti dan situs yang sedang berjalan tidak berubah.

- Nama lingkungan (`staging` atau `production`) sama dengan `APP_ENV` di `.env` folder itu.
- Arsip dibangun dari commit yang sekarang ada di `origin/main` (file `RELEASE_COMMIT` di dalam arsip).
- Aturan klinis sudah disetujui (`php artisan clinical:gate`, PRD 10). Pengecualian `ALLOW_UNREVIEWED=1` dicatat di
  `storage/logs/deploy-overrides.log` dan tidak boleh dipakai bila ada pasien sungguhan.
- Production dengan `PILOT_MODE=true`: pemeriksaan go-live `php artisan pilot:check` (dibuat di E10; sampai saat itu dilewati).

`DEPLOY_CHECK_ONLY=1 bash scripts/deploy.sh <lingkungan> release.tar.gz` hanya menjalankan pemeriksaan.
Setelah deploy, commit yang berjalan tertulis di `storage/app/revision.txt`.

## Menyiapkan staging (sekali)

1. **hPanel:** tambahkan subdomain `staging.telefisiorehab.com`, aktifkan SSL, buat database MySQL dan user **terpisah**
   dari production.
2. **Document root:** arahkan subdomain ke folder `public/` aplikasi staging, dengan cara yang sama seperti production
   **[perlu diisi: cara yang dipakai production, mis. symlink `public_html` atau document root di hPanel]**.
3. **SSH:** `git clone https://github.com/basazard/telefisiorehab_web.git <folder staging>`, lalu di folder itu
   `cp .env.example .env` dan `php artisan key:generate`.
4. **`.env` staging** (isi nilai Anda sendiri; jangan pernah di-commit):

   ```
   APP_ENV=staging
   APP_DEBUG=false
   APP_URL=https://staging.telefisiorehab.com
   DB_DATABASE=<database staging>
   DB_USERNAME=<user staging>
   DB_PASSWORD=<kata sandi>
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.hostinger.com
   MAIL_PORT=465
   MAIL_USERNAME=<mailbox>
   MAIL_PASSWORD=<kata sandi mailbox>
   MAIL_FROM_ADDRESS=<mailbox>
   MAIL_ALWAYS_TO=<alamat Anda>      # wajib di staging: semua email hanya ke Anda
   PILOT_MODE=true
   REGISTRATION_MODE=clinic_code
   SUPPORT_EMAIL=<email dukungan>
   SESSION_LIFETIME=60
   SESSION_SAME_SITE=lax
   LOG_CHANNEL=daily
   LOG_DAILY_DAYS=14
   LOG_LEVEL=warning
   ```

5. **Rilis pertama:** bangun dan unggah `release.tar.gz`, jalankan `bash scripts/deploy.sh staging release.tar.gz`, lalu
   `php artisan db:seed --class=DemoSeeder` (data demo hanya boleh di staging dan local).
6. **Periksa:** `bash scripts/smoke.sh https://staging.telefisiorehab.com --noindex` harus hijau, dan email dari staging
   (daftar akun, lupa kata sandi) hanya sampai ke alamat `MAIL_ALWAYS_TO`.
