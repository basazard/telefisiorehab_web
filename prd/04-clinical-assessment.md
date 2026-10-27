# Product Requirement Document (PRD)

## Modul Clinical Assessment (Identifikasi Domain Fisioterapi) – TeleFisioRehab

> **Status:** Terimplementasi · **PRD asli:** `Flow Sriyani/md/4_prd_modul_clinical_assessment_telefisiorehab.md`
>
> **Status klinis:** nilai dan aturan klinis modul ini **disetujui 2026-10-26** (versi aturan `2026.1`, keputusan di `prd/clinical/decisions.csv`) oleh satu reviewer (Dokter Umum). PRD 10 §2 meminta fisioterapis; **pemilik memutuskan review Dokter Umum ini juga mewakili fisioterapi** (2026-10-27). Kata "DRAFT" di bawah menggambarkan status sebelum review.

---

### 1. Ringkasan Produk & Tujuan

Setelah lolos screening tanpa red flag, sistem **menentukan satu dari tujuh domain fisioterapi** berdasarkan keluhan utama dan
profil, menampilkan instrumen yang sesuai domain, menghitung skor, dan menjawab pertanyaan *"Apakah ditemukan keterbatasan
fungsi atau risiko?"*. Pada versi ini pengguna mengisi **instrumen mandiri**; tes yang memerlukan fisioterapis atau alat
ditampilkan sebagai "Dinilai oleh fisioterapis".

---

### 2. Alur Pengguna (User Flow)

```
[Screening selesai, tanpa red flag]
   │
   ▼
Identifikasi Domain (otomatis, di server) ── tampil: domain, alasan, parameter fokus, instrumen
   │
   ▼
Clinical Assessment: satu instrumen per langkah (dapat dilanjutkan nanti)
   │
   ▼
Analisis Hasil ── "Apakah ditemukan keterbatasan fungsi atau risiko?"
   ├── Ya    ──► Klasifikasi Risiko (Modul 5)
   └── Tidak ──► Edukasi Pencegahan & Latihan Mandiri ──► Rekomendasi Intervensi (Modul 6)
   │
   ▼
Status assessment: Selesai (Risk Stratification langsung dijalankan)
```

---

### 3. Detail Kebutuhan Fitur (Sebagaimana Dibangun)

#### 3.1 Penentuan Domain (DRAFT, aturan pertama yang cocok)
| Urutan | Kondisi | Domain |
| :-- | :-- | :-- |
| 1 | Usia < 18 tahun | Pediatri |
| 2 | Gangguan neurologis (profil) | Neuromuskular |
| 3 | Keluhan sesak / kelelahan saat aktivitas | Kardiopulmonal |
| 4 | Penyakit jantung atau paru (profil) | Kardiopulmonal |
| 5 | Keluhan gangguan fungsi pasca cedera / operasi | Post Operasi / Cedera |
| 6 | Pasca operasi atau cedera (profil) | Post Operasi / Cedera |
| 7 | Usia ≥ 60 tahun | Geriatri |
| 8 | Pekerjaan "Atlet" | Olahraga |
| 9 | Keluhan gangguan keseimbangan | Neuromuskular |
| – | Selain itu | Muskuloskeletal |

Domain disimpan sebagai *snapshot* beserta alasannya; satu assessment per screening.

#### 3.2 Instrumen per Domain
| Domain | Diisi pengguna | Dinilai fisioterapis |
| :-- | :-- | :-- |
| Muskuloskeletal | Instrumen area: LEFS (tungkai), ODI (punggung bawah), SPADI (bahu), atau PSFS | ROM, MMT, postur |
| Neuromuskular | Barthel Index, TUG | BBS, MMT, MAS, pola berjalan |
| Kardiopulmonal | mMRC, 1-minute sit to stand + Borg, tanda vital (opsional) | 6MWT |
| Pediatri | – | Denver II/KPSP, GMFM, motorik, observasi bermain |
| Geriatri | Checklist risiko jatuh (CDC STEADI), TUG, Barthel Index | BBS, MMSE |
| Post Operasi / Cedera | Instrumen area + edema + status luka | ROM, MMT, fungsi spesifik |
| Olahraga | Instrumen area + PSFS | FMS, power test, sport specific test |

Skala nyeri (NRS) diambil dari screening. TUG dan sit to stand menampilkan peringatan **"hanya dengan pendamping"** dan
opsi "Tidak dapat / tidak aman melakukan".

#### 3.3 Analisis: "Keterbatasan fungsi atau risiko?" (DRAFT)
"Ya" jika salah satu terpenuhi: NRS ≥ 4, LEFS < 60/80, ODI > 20 %, SPADI > 20 %, PSFS rata-rata < 7, Barthel < 100,
skor risiko jatuh ≥ 4/14, TUG ≥ 12 detik atau tidak dapat, mMRC ≥ 2, sit to stand < 20 kali atau Borg ≥ 5 atau tidak dapat,
SpO2 < 95 %, nadi istirahat > 100, napas > 20/menit, edema sedang/berat atau selisih lingkar ≥ 2 cm, tanda infeksi luka.
**Pediatri** (tanpa instrumen mandiri) selalu "Ya" demi keamanan.

#### 3.4 Hasil & Catatan UI
* Halaman hasil: skor per instrumen dengan penanda "Perlu perhatian", daftar temuan, cabang Ya/Tidak.
* Cabang **Tidak** menampilkan edukasi pencegahan (DRAFT): tetap aktif 150 menit/minggu, jeda duduk 30–60 menit,
  pemanasan/pendinginan, progresi bertahap ±10 %/minggu, postur mengangkat, kapan menghentikan latihan.
* Peringatan merah untuk SpO2 < 90 % dan tanda infeksi luka (dieskalasi oleh Modul 5).
* Catatan sistem: pemilihan instrumen dinamis, hasil menjadi dasar klasifikasi risiko, **tidak menggantikan pemeriksaan
  langsung**.

---

### 4. Kriteria Keberhasilan

| Kriteria | Status |
| :-- | :-- |
| Instrumen tampil otomatis sesuai domain | ✅ (instrumen mandiri) |
| Perhitungan skor akurat dan konsisten | ✅ diuji per instrumen |
| Cabang Klasifikasi Risiko / Edukasi otomatis | ✅ |
| Data assessment tersimpan dan dipakai modul berikutnya | ✅ |

---

### 5. Keputusan Implementasi & Perbedaan dengan PRD Asli

* **Instrumen fisioterapis/alat belum diisi di aplikasi** (menunggu sisi terapis). Ditampilkan sebagai daftar tunggu.
* Pediatri tidak memiliki kuesioner mandiri; KPSP berbeda per usia dan dikerjakan bersama fisioterapis.
* **Lisensi:** ODI, SPADI, dan Borg CR10 berhak cipta. **Periksa sebelum peluncuran.**
* **DRAFT:** urutan aturan domain, semua cut-off, dan teks edukasi memerlukan persetujuan klinis.

---

### 6. Lokasi Implementasi

* Halaman: `pages/patient/domain`, `assessment`, `assessment-result`; komponen `x-instrument-fields`, `x-clinical-disclaimer`
* Aturan: `app/Support/Onboarding/ClinicalAssessment.php`, `Instruments.php`; enum `PhysioDomain`
* Data: tabel `assessments` (model `AssessmentResult`)
