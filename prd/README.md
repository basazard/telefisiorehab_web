# PRD Alur Pasien – TeleFisioRehab (versi terimplementasi)

Folder ini berisi PRD setiap modul alur pasien **sebagaimana sudah dibangun di aplikasi**, dari profil sampai monitoring dan
maintenance. Setiap dokumen mengikuti struktur PRD asli (`Flow Sriyani/md/`), ditambah:

* **Keputusan implementasi**: pilihan yang diambil saat membangun modul (dan alasannya).
* **Nilai DRAFT**: ambang batas dan aturan klinis yang **wajib ditinjau dan disetujui fisioterapis/dokter sebelum peluncuran**.
* **Perbedaan dengan PRD asli**: bagian PRD yang belum atau sengaja tidak dibangun.
* **Lokasi implementasi**: file kode untuk tim pengembang.

Status per 21 Oktober 2026.

---

## Daftar Modul

| No | Modul | Dokumen | Halaman utama |
| :-- | :-- | :-- | :-- |
| 1 | User Profile | [01-user-profile.md](01-user-profile.md) | `/patient/profile/setup` |
| 2 | Initial Screening | [02-initial-screening.md](02-initial-screening.md) | `/patient/screening` |
| 3 | Red Flag Screening | [03-red-flag-screening.md](03-red-flag-screening.md) | langkah 4 screening, `/patient/screening/stopped` |
| 4 | Clinical Assessment (Identifikasi Domain) | [04-clinical-assessment.md](04-clinical-assessment.md) | `/patient/domain`, `/patient/assessment` |
| 5 | Scoring & Risk Stratification | [05-scoring-risk-stratification.md](05-scoring-risk-stratification.md) | `/patient/risk-classification` |
| 6 | Exercise / Telefisioterapi Recommendation | [06-exercise-recommendation.md](06-exercise-recommendation.md) | `/patient/intervention`, `/patient/program` |
| 7 | Monitoring & Reassessment (+ Maintenance) | [07-monitoring-reassessment.md](07-monitoring-reassessment.md) | `/patient/evaluation` |
| 8 | Hardening (perbaikan fondasi, non-fitur) | [08-hardening.md](08-hardening.md) | – |

---

## Alur Lengkap

```
[Registrasi] ──► 1. User Profile ──► 2. Initial Screening (keluhan, body map, karakteristik)
                                          │
                                          ▼
                                     3. Red Flag Screening ──[Ya / nyeri ≥ 9]──► STOP (halaman rujukan,
                                          │                                       dibuka hanya oleh staf klinik)
                                       [Tidak]
                                          ▼
                    4. Identifikasi Domain (1 dari 7) ──► Clinical Assessment (kuesioner mandiri)
                                          │
                                          ▼
                    5. Scoring & Risk Stratification ──[gawat darurat]──► STOP (seperti red flag)
                                          │
                     ┌────────────────────┼─────────────────────┐
                     ▼                    ▼                     ▼
               Risiko Rendah        Risiko Sedang          Risiko Tinggi
             (Self Management)      (Terbimbing)       (Konsultasi / Rujukan,
                     │                    │              TANPA latihan mandiri)
                     └──────► 6. Program Latihan + Log Latihan
                                          │
                                          ▼
                    7. Monitoring berkala (1–4 minggu) ──► Progresif / Modifikasi / Reassessment / Rujukan
                                          │
                                  [Tujuan tercapai]
                                          ▼
                                 Maintenance Program (monitoring tetap berjalan)
```

---

## Aturan Lintas Modul

1. **Keputusan keselamatan selalu di server.** Red flag, kategori risiko, pemblokiran latihan mandiri, dan eskalasi gawat
   darurat dihitung di server dari jawaban mentah, tidak pernah diterima dari browser.
2. **Red-flag lock.** Selama ada red flag aktif, semua halaman pasien (dashboard, program, assessment, monitoring, riwayat)
   dialihkan ke halaman rujukan. Hanya terapis/admin klinik yang sama atau platform admin yang dapat membuka kunci,
   dengan catatan tertulis minimal 10 karakter.
3. **Profil wajib lengkap** sebelum screening dan modul berikutnya dapat diakses.
4. **Data kesehatan per klinik.** Staf hanya melihat data pasien kliniknya; pasien hanya melihat datanya sendiri.
5. **Sanggahan medis** tampil di modul klinis: TeleFisioRehab tidak menggantikan pemeriksaan langsung oleh tenaga kesehatan.

---

## Wajib Ditinjau Sebelum Peluncuran

| Area | Isi DRAFT | Lokasi kode |
| :-- | :-- | :-- |
| Red flag | Daftar pertanyaan, ambang nyeri ≥ 9 | `app/Support/Onboarding/RedFlags.php` |
| Clinical Assessment | Aturan pemilihan domain, cut-off keterbatasan, teks edukasi | `ClinicalAssessment.php`, `Instruments.php` |
| Risk Stratification | Rentang Rendah/Sedang/Tinggi per instrumen, aturan gawat darurat | `RiskStratification.php` |
| Exercise Recommendation | Pustaka 28 latihan, dosis, progresi, edukasi, aturan "ada perbaikan" | `ExerciseLibrary.php`, `Intervention.php` |
| Monitoring | MCID per instrumen, periode reassessment 4 minggu, aturan "tujuan tercapai" | `Monitoring.php` |
| Lisensi | ODI, SPADI, Borg CR10 (berhak cipta) | – |

## Fitur yang Ditunda

* Pengingat push notification / email (saat ini hanya pengingat di dalam aplikasi).
* Video call telekonsultasi (saat ini berupa permintaan yang ditangani staf).
* Dashboard terapis dan klinik.
* Unggah foto/video progres.
* Video latihan (`exercises.video_url` masih kosong).
