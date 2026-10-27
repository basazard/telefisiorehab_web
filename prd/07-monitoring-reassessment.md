# Product Requirement Document (PRD)

## Modul Monitoring & Reassessment (+ Maintenance Program) – TeleFisioRehab

> **Status:** Terimplementasi (dengan fitur ditunda) · **PRD asli:** `Flow Sriyani/md/7_prd_modul_monitoring_reassessment_telefisiorehab.md`
>
> **Status klinis:** nilai dan aturan klinis modul ini **disetujui 2026-10-26** (versi aturan `2026.1`, keputusan di `prd/clinical/decisions.csv`) oleh satu reviewer (Dokter Umum). **Review fisioterapis belum dilakukan** (PRD 10 §2 dan §8; keputusan pemilik, opsi C). Kata "DRAFT" di bawah menggambarkan status sebelum review.

---

### 1. Ringkasan Produk & Tujuan

Modul ini memantau perkembangan pengguna secara berkala dengan **mengulang instrumen assessment awal** dan **membandingkan
setiap skor dengan baseline**. Sistem menentukan apakah ada perbaikan signifikan, menyesuaikan program (progresif,
modifikasi, reassessment, atau rujukan), dan mengalihkan pengguna ke **Maintenance Program** saat tujuan fisioterapi tercapai.

---

### 2. Alur Pengguna (User Flow)

```
[Program aktif] ── pengingat di aplikasi saat jadwal tiba (Rendah 4 mgg · Sedang 2 mgg · Maintenance 4 mgg)
   │
   ▼
Pengisian Monitoring
   1. Kondisi umum: NRS, perubahan kondisi & kualitas hidup, keluhan baru, efek samping, hambatan latihan
   2..n. Instrumen domain yang sama dengan assessment awal
   │
   ▼
Analisis: bandingkan dengan baseline (MCID per instrumen) + kategori risiko saat ini + kepatuhan dari log latihan
   │
   ▼
Apakah ada perbaikan signifikan?
   ├── Ya    ──► PROGRESIF: level intensitas +1, tambah variasi lebih menantang, jadwal monitoring berikutnya
   └── Tidak ──► MODIFIKASI: kepatuhan < 50 % → fokus kepatuhan; selain itu dosis turun 1 level; tips hambatan; konsultasi
                 REASSESSMENT: memburuk, atau 4+ minggu tanpa perbaikan → program dijeda, screening ulang, konsultasi ulang
                 RUJUKAN: perburukan drastis → program dijeda, anjuran rujukan (gawat darurat → red flag)
   │
   ▼
Apakah tujuan fisioterapi tercapai?
   ├── Ya    ──► Program SELESAI ──► MAINTENANCE PROGRAM (monitoring tetap berjalan)
   └── Tidak ──► tetap dalam siklus monitoring
```

---

### 3. Detail Kebutuhan Fitur (Sebagaimana Dibangun)

#### 3.1 Metode & Parameter Monitoring
| Parameter PRD | Implementasi |
| :-- | :-- |
| Skala nyeri | NRS 0–10 |
| Fungsi aktivitas | Instrumen domain dari assessment awal (LEFS, ODI, SPADI, PSFS, Barthel, TUG, mMRC, sit to stand, …) |
| Mobilitas & keseimbangan | TUG, risiko jatuh, Barthel (sesuai domain) |
| Kondisi umum | Tanda vital (domain kardiopulmonal), perubahan kondisi & kualitas hidup |
| Kepatuhan latihan | Otomatis dari log latihan: % hari berlatih sejak monitoring terakhir |
| Keluhan baru / efek samping | Ya/Tidak + uraian efek samping |
| Hambatan | Nyeri, motivasi, waktu, tempat/alat, teknik, sering lupa |
| ROM, kekuatan otot (MMT) | Dinilai fisioterapis saat telekonsultasi |

#### 3.2 Perbandingan Otomatis dengan Baseline (MCID, DRAFT)
| Parameter | Arah membaik | Perubahan bermakna |
| :-- | :-- | :-- |
| NRS | turun | ≥ 2 |
| LEFS | naik | ≥ 9 |
| ODI | turun | ≥ 10 poin % |
| SPADI | turun | ≥ 13 poin % |
| PSFS | naik | ≥ 2 |
| Barthel Index | naik | ≥ 10 |
| Risiko jatuh (STEADI) | turun | ≥ 2 |
| TUG | turun | ≥ 2 detik |
| mMRC | turun | ≥ 1 |
| 1-min sit to stand | naik | ≥ 3 kali |
| Edema | turun | ≥ 1 tingkat |

"Tidak dapat melakukan tes" dihitung sebagai nilai terburuk. Tanda vital dan status luka tidak dibandingkan, tetapi dipakai
untuk kategori risiko dan eskalasi gawat darurat. Hasil tampil sebagai tabel **Awal · Sekarang · Perubahan** dengan label
Membaik bermakna / Sedikit membaik / Tetap / Sedikit memburuk / Memburuk.

#### 3.3 Algoritma Keputusan (DRAFT)
* **Memburuk** = ada parameter memburuk bermakna, kondisi umum "lebih buruk", keluhan baru, atau efek samping.
* **Perbaikan signifikan** = minimal satu parameter membaik bermakna **dan** tidak memburuk.
* **Perburukan drastis** = nyeri naik ≥ 3, kategori risiko saat ini Tinggi, atau gawat darurat (SpO2 < 90 %, infeksi luka).

| Prioritas | Kondisi | Hasil |
| :-- | :-- | :-- |
| 1 | Perburukan drastis | **Rujukan**: program dijeda; gawat darurat dikunci seperti red flag |
| 2 | Tujuan tercapai | **Maintenance Program** |
| 3 | Perbaikan signifikan | **Progresif**: level +1 (maks. 3) dan satu variasi latihan baru |
| 4 | Memburuk, atau ≥ 4 minggu tanpa perbaikan | **Reassessment**: program dijeda, screening ulang, konsultasi ulang |
| 5 | Selain itu | **Modifikasi**: kepatuhan < 50 % → fokus kepatuhan; selain itu dosis turun 1 level |

#### 3.4 Tujuan Fisioterapi Tercapai & Maintenance (DRAFT)
* **Tercapai** bila kategori risiko dari jawaban monitoring = **Rendah**, **NRS ≤ 2**, dan tidak ada yang memburuk.
* Program ditutup ("Tujuan fisioterapi tercapai") dan **Maintenance Program** dibuat: peregangan, penguatan, dan jalan kaki
  sesuai area tubuh, dosis Rendah level 2, monitoring tiap 4 minggu. Maintenance tetap dimonitor; bila memburuk, alur
  reassessment/rujukan berlaku seperti biasa.

#### 3.5 Halaman Hasil Monitoring
Tabel perbandingan, kepatuhan, kategori risiko saat ini, jawaban dua pertanyaan keputusan, langkah lanjut sesuai hasil
(tips per hambatan, tombol konsultasi ulang / minta rujukan / mulai reassessment / buka Maintenance Program), dan sanggahan
medis.

---

### 4. Kriteria Keberhasilan

| Kriteria | Status |
| :-- | :-- |
| Selisih skor terhadap baseline dihitung akurat | ✅ diuji per instrumen |
| Alur progresif vs. reassessment/modifikasi dibedakan otomatis | ✅ |
| Pengingat jadwal monitoring (1, 2, atau 4 minggu) | ⚠️ di dalam aplikasi saja; push notification ditunda |
| Setelah tujuan tercapai, pengguna dialihkan ke Maintenance Program | ✅ oleh sistem; penandaan oleh fisioterapis menunggu sisi terapis |

---

### 5. Keputusan Implementasi & Perbedaan dengan PRD Asli

* Monitoring **menggantikan evaluasi 0–10 sederhana** dari Modul 6 dengan instrumen domain yang sama seperti assessment awal.
* **Respons bertahap** saat tidak ada perbaikan (modifikasi dulu, reassessment setelah 4 minggu), bukan langsung menjeda.
* **Ditunda:** push notification, unggah foto/video progres, penandaan "tujuan tercapai" oleh fisioterapis.
* **DRAFT:** MCID, periode reassessment 4 minggu, aturan perburukan drastis, aturan tujuan tercapai, dan tips hambatan
  memerlukan persetujuan klinis.

---

### 6. Lokasi Implementasi

* Aturan: `app/Support/Onboarding/Monitoring.php`; `Programs::monitor()`, `Programs::buildMaintenance()`
* Halaman: `pages/patient/evaluation.blade.php` (rute `patient.evaluation`), komponen `x-instrument-fields`
* Data: tabel `program_evaluations` (jawaban, perbandingan, kategori, hambatan, hasil, tujuan tercapai), `programs.pathway = maintenance`
