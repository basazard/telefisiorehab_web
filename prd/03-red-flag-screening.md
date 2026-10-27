# Product Requirement Document (PRD)

## Modul Red Flag Screening – TeleFisioRehab

> **Status:** Terimplementasi (dengan catatan) · **PRD asli:** `Flow Sriyani/md/3_prd_modul_red_flag_screening_telefisiorehab.md`
>
> **Status klinis:** nilai dan aturan klinis modul ini **disetujui 2026-10-26** (versi aturan `2026.1`, keputusan di `prd/clinical/decisions.csv`) oleh satu reviewer (Dokter Umum). **Review fisioterapis belum dilakukan** (PRD 10 §2 dan §8; keputusan pemilik, opsi C). Kata "DRAFT" di bawah menggambarkan status sebelum review.

---

### 1. Ringkasan Produk & Tujuan

**Red Flag Screening** mendeteksi kondisi gawat darurat atau penyakit serius. Satu jawaban "Ya" (atau nyeri ≥ 9) langsung
**menghentikan program latihan di server** dan mengarahkan pengguna ke fasilitas kesehatan. Akses hanya dapat dibuka kembali
oleh staf klinik setelah pasien diperiksa tenaga medis.

---

### 2. Alur Pengguna (User Flow)

```
[Langkah 4 Initial Screening]
"Apakah Anda mengalami salah satu kondisi berikut?" (Ya / Tidak untuk setiap kondisi, semua wajib dijawab)
   │
   ├── [ada "Ya", atau nyeri ≥ 9] ──► RISIKO TINGGI (KEGAWATDARURATAN)
   │                                   ├── Program latihan dikunci (server)
   │                                   ├── Halaman rujukan merah: periksa ke faskes, 112 / 119 / IGD
   │                                   └── Terkunci sampai staf klinik membuka dengan catatan
   │
   └── [semua "Tidak"] ──► indikator hijau "Tidak ada tanda bahaya" ──► Kelompok khusus ──► Identifikasi Domain
```

Red flag juga dapat dipicu **otomatis** oleh modul lain: nyeri ≥ 9 (screening), SpO2 < 90 % atau tanda infeksi luka
(Risk Stratification dan Monitoring).

---

### 3. Detail Kebutuhan Fitur (Sebagaimana Dibangun)

#### 3.1 Daftar Pertanyaan
| Kunci | Pertanyaan | Di PRD |
| :-- | :-- | :-- |
| chest_pain | Nyeri dada atau dada terasa tertekan? | ✅ |
| severe_breathlessness | Sesak napas berat? | ✅ |
| fainting | Pingsan atau hilang kesadaran? | ✅ (penurunan kesadaran) |
| sudden_weakness | Kelemahan mendadak pada lengan atau tungkai? | ✅ |
| speech_difficulty | Gangguan bicara mendadak (pelo / sulit berbicara)? | ✅ |
| incontinence | Hilang kontrol BAK / BAB secara mendadak? | ✅ |
| saddle_numbness | Baal di area selangkangan atau pangkal paha bagian dalam? | ⚠️ sebagian (lihat catatan) |
| high_fever | Demam tinggi (di atas 38,5 °C)? | ✅ |
| major_trauma | Trauma berat atau kecelakaan baru-baru ini? | ✅ |
| suspected_fracture | Dugaan patah tulang? | tambahan |
| uncontrolled_bleeding | Perdarahan yang tidak berhenti? | ✅ |
| sudden_severe_headache | Sakit kepala hebat mendadak? | tambahan |
| unexplained_weight_loss | Penurunan berat badan cepat tanpa sebab yang jelas? | ✅ |
| night_pain | Nyeri malam hari yang membuat sulit tidur dan tidak membaik? | tambahan |
| severe_pain | Nyeri sangat berat yang tidak tertahankan? | ✅ |
| other | Ada tanda bahaya lain? (wajib dijelaskan jika "Ya") | ✅ |

**Red flag otomatis:** `pain_score_high` (nyeri 9–10), `spo2_critical` (SpO2 < 90 %), `wound_infection` (tanda infeksi luka).

#### 3.2 Decision Logic (selalu di server)
* **JIKA ada "Ya" atau red flag otomatis**: screening berstatus *stopped*, pencatatan red flag berisi waktu penghentian,
  semua halaman pasien dialihkan ke halaman rujukan.
* **JIKA tidak ada**: catatan red flag disimpan sebagai *cleared*, lanjut ke langkah 5 dengan indikator hijau.

#### 3.3 Halaman Rujukan (pasien)
Banner merah kontras tinggi, daftar tanda bahaya yang dilaporkan, langkah: periksa ke faskes terdekat, 112/119/IGD bila
berat, siapa yang dapat membuka akses (terapis klinik, atau penyedia layanan jika tanpa klinik). Halaman tidak dapat ditutup
sampai akses dibuka.

#### 3.4 Pembukaan Kunci (staf)
* Halaman **Red flags** untuk terapis, admin klinik, dan platform admin; staf hanya melihat pasien kliniknya.
* Pembukaan kunci wajib dengan **catatan tertulis minimal 10 karakter**; dicatat siapa dan kapan.
* Pasien tanpa klinik hanya dapat dibuka oleh platform admin.

---

### 4. Kriteria Keberhasilan

| Kriteria | Status |
| :-- | :-- |
| Minimal 1 red flag memblokir akses latihan dan menampilkan halaman darurat | ✅ |
| Tanpa red flag, pengguna dapat lanjut ke Identifikasi Domain | ✅ |
| Setiap respons tersimpan sebagai jejak audit untuk verifikasi fisioterapis | ✅ jawaban, red flag, pembuka kunci, catatan |
| Instruksi darurat jelas dan kontras tinggi | ✅ |

---

### 5. Keputusan Implementasi & Perbedaan dengan PRD Asli

* **Posisi:** red flag dijalankan pada **langkah 4 Initial Screening** (mengikuti PRD 2), bukan sebagai pintu masuk pertama
  seperti PRD 3. Nyeri ≥ 9 sudah dihentikan pada langkah 3.
* **Belum ada:** *"Gangguan penglihatan mendadak"*. **Perlu ditambahkan.**
* **Sebagian:** *"Mati rasa pada area tertentu yang muncul tiba-tiba"* saat ini hanya baal area selangkangan;
  *"Demam tinggi disertai keluhan muskuloskeletal"* saat ini demam > 38,5 °C saja.
* **Tambahan dari aplikasi lama:** dugaan patah tulang, sakit kepala hebat mendadak, nyeri malam hari.
* **Catatan UI PRD 3.3** ("jika ragu, konsultasikan"; "tidak menggantikan pemeriksaan medis") belum tampil di langkah ini.
* **DRAFT:** daftar pertanyaan dan ambang nyeri ≥ 9 memerlukan persetujuan klinis.

---

### 6. Lokasi Implementasi

* Aturan: `app/Support/Onboarding/RedFlags.php`
* Halaman: langkah 4 `screening.blade.php`, `pages/patient/red-flag-stop.blade.php`, `pages/therapist/red-flag-review.blade.php`
* Akses: middleware `redflag.clear` (`EnsureNotRedFlagLocked`), `RedFlagCheckPolicy`; data `red_flag_checks`
