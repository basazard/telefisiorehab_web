# Product Requirement Document (PRD)

## Modul Scoring & Risk Stratification – TeleFisioRehab

> **Status:** Terimplementasi · **PRD asli:** `Flow Sriyani/md/5_prd_scoring_risk_stratification_telefisiorehab.md`
>
> **Status klinis:** nilai dan aturan klinis modul ini **disetujui 2026-10-26** (versi aturan `2026.1`, keputusan di `prd/clinical/decisions.csv`) oleh satu reviewer (Dokter Umum). PRD 10 §2 meminta fisioterapis; **pemilik memutuskan review Dokter Umum ini juga mewakili fisioterapi** (2026-10-27). Kata "DRAFT" di bawah menggambarkan status sebelum review.

---

### 1. Ringkasan Produk & Tujuan

Modul ini memproses hasil Clinical Assessment menjadi **kategori risiko Rendah, Sedang, atau Tinggi** yang menjadi dasar
prioritas intervensi. Perhitungan berjalan **otomatis di server** begitu assessment selesai. Kondisi gawat darurat yang
ditemukan langsung dieskalasi seperti red flag.

---

### 2. Alur Pengguna (User Flow)

```
[Assessment selesai]
   │
   ▼
Hitung rentang setiap instrumen (Rendah / Sedang / Tinggi)
   │
   ▼
Kategori keseluruhan = rentang TERTINGGI yang ditemukan
   │
   ├── Gawat darurat (SpO2 < 90 %, infeksi luka) ──► STOP (red flag, halaman rujukan)
   ├── Risiko Rendah  ──┐
   ├── Risiko Sedang  ──┼──► Simpan kategori & detail ──► "Lanjut ke Modul Rekomendasi Intervensi"
   └── Risiko Tinggi  ──┘    (Tinggi: peringatan khusus + perlu supervisi fisioterapis)
```

---

### 3. Detail Kebutuhan Fitur (Sebagaimana Dibangun)

#### 3.1 Matriks Rentang per Instrumen (DRAFT)
"Sedang" dimulai tepat pada cut-off keterbatasan Clinical Assessment, sehingga **tidak ada keterbatasan = Risiko Rendah**.

| Instrumen | Rendah | Sedang | Tinggi |
| :-- | :-- | :-- | :-- |
| NRS | 0–3 | 4–6 | 7–10 |
| LEFS (0–80) | ≥ 60 | 30–59 | < 30 |
| ODI | ≤ 20 % | 21–40 % | > 40 % |
| SPADI | ≤ 20 % | 21–50 % | > 50 % |
| PSFS (rata-rata) | ≥ 7 | 4–6,9 | < 4 |
| Barthel Index | 100 | 61–99 | ≤ 60 |
| Risiko jatuh (STEADI) | < 4 | 4–6 | ≥ 7 |
| TUG | < 12 dtk | 12–19,9 dtk | ≥ 20 dtk / tidak dapat |
| mMRC | 0–1 | 2 | 3–4 |
| 1-min sit to stand | ≥ 20 kali dan Borg < 5 | 12–19 kali atau Borg 5–6 | < 12 kali, Borg ≥ 7, atau tidak dapat |
| Tanda vital | normal / tidak diisi | SpO2 90–94 %, nadi 101–120, napas 21–24 | SpO2 < 90 %, nadi > 120, napas > 24 |
| Edema | tidak ada / ringan, selisih < 2 cm | sedang, atau selisih 2–3,9 cm | berat, atau selisih ≥ 4 cm |
| Status luka | tidak ada / bersih | – | tanda infeksi |
| Pediatri (tanpa instrumen mandiri) | – | menunggu fisioterapis | – |

#### 3.2 Kategori & Kriteria (ditampilkan ke pengguna)
* **Risiko Rendah:** nyeri ringan (NRS 0–3), fungsi baik/mandiri, risiko jatuh rendah, tanpa keterbatasan bermakna, vital normal.
* **Risiko Sedang:** nyeri sedang (NRS 4–6), keterbatasan ringan–sedang, risiko jatuh sedang, penurunan kekuatan/ROM,
  toleransi aktivitas menurun.
* **Risiko Tinggi:** nyeri berat (NRS 7–10), keterbatasan berat, risiko jatuh tinggi, kelemahan signifikan, vital tidak
  stabil, dampak besar pada aktivitas sehari-hari.

#### 3.3 Eskalasi Gawat Darurat
* **SpO2 < 90 %** dan **tanda infeksi luka** menghentikan program **seperti red flag**: pasien diarahkan ke halaman rujukan,
  staf klinik melihatnya di halaman Red flags, dan hanya staf yang dapat membuka kunci dengan catatan.
* **Risiko Tinggi** (tanpa gawat darurat): peringatan merah "Perlu perhatian tenaga kesehatan" + 112/119/IGD; pengguna tetap
  dapat lanjut, dan hasil ditandai **perlu supervisi fisioterapis** untuk Modul 6.

#### 3.4 Halaman Klasifikasi Risiko
Kategori dengan kriteria, tabel skor per instrumen beserta rentangnya, daftar instrumen fisioterapis yang belum termasuk,
CTA **"Lanjut ke Modul Rekomendasi Intervensi"**, dan catatan: cut-off disesuaikan pedoman klinis, **bukan diagnosis medis**,
hasil diinterpretasikan fisioterapis, kondisi gawat darurat langsung ke faskes.

---

### 4. Kriteria Keberhasilan

| Kriteria | Status |
| :-- | :-- |
| Perhitungan skor tanpa error | ✅ diuji per instrumen dan batas rentang |
| Kategori Rendah/Sedang/Tinggi otomatis sesuai cut-off | ✅ |
| Kategori dan skor detail tersimpan dan dipakai Modul 6 | ✅ |
| Risiko Tinggi memicu peringatan dan arahan medis | ✅ (gawat darurat = hard stop) |

---

### 5. Keputusan Implementasi & Perbedaan dengan PRD Asli

* **Kategori = rentang tertinggi** (pendekatan konservatif).
* **Risiko Tinggi tidak dikunci**, tetapi diperingatkan dan disupervisi; **gawat darurat dikunci** seperti red flag.
* Assessment yang selesai sebelum modul ini dihitung saat halaman pertama kali dibuka.
* Reassessment yang menghasilkan Risiko Tinggi **menghentikan program latihan yang sedang berjalan**.
* **DRAFT:** semua rentang dan aturan gawat darurat memerlukan persetujuan klinis.

---

### 6. Lokasi Implementasi

* Aturan: `app/Support/Onboarding/RiskStratification.php`; `AssessmentResult::stratify()`, `escalate()`; enum `RiskTier`
* Halaman: `pages/patient/risk-classification.blade.php`
* Data: kolom `tier`, `risk_details`, `stratified_at` pada tabel `assessments`
