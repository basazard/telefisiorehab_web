# Product Requirement Document (PRD)

## Modul Initial Screening – TeleFisioRehab

> **Status:** Terimplementasi · **PRD asli:** `Flow Sriyani/md/2_prd_modul_initial_screening_telefisiorehab.md`

---

### 1. Ringkasan Produk & Tujuan

Modul **Initial Screening** menilai keluhan utama, lokasi dan karakteristik keluhan, lalu menjalankan **Red Flag Screening**
(lihat [03-red-flag-screening.md](03-red-flag-screening.md)). Jika aman, sistem menampilkan kelompok khusus yang perlu
perhatian sebelum pengguna masuk ke **Identifikasi Domain Fisioterapi**. Setiap screening disimpan sebagai episode baru,
sehingga riwayat tetap ada saat pengguna melakukan reassessment.

---

### 2. Alur Pengguna (User Flow)

```
[Mulai] ── data profil diambil (usia, jenis kelamin, kondisi khusus)
   │
   ▼
1. Keluhan Utama (multi-select)
   │
   ▼
2. Lokasi Keluhan (Body Map, 35 area)  ── boleh dilewati jika keluhan tidak terlokalisasi
   │
   ▼
3. Karakteristik Keluhan (pertanyaan menyesuaikan keluhan)
   │        └── nyeri ≥ 9 di area mana pun ──► STOP (red flag)
   ▼
4. Red Flag Screening ──[ada "Ya"]──► STOP (halaman rujukan)
   │
 [Tidak]
   ▼
5. Kelompok Khusus (ditampilkan; tanda hijau "Tidak ada tanda bahaya")
   │
   ▼
[Lanjut ke Identifikasi Domain Fisioterapi]
```

Progres tersimpan di setiap langkah; pengguna dapat keluar dan melanjutkan dari langkah terakhir. Browser tidak dapat
melompati langkah.

---

### 3. Detail Kebutuhan Fitur (Sebagaimana Dibangun)

#### Langkah 1: Keluhan Utama
Minimal satu: Nyeri, Kelemahan otot, Kesulitan bergerak, Gangguan keseimbangan, Kesulitan berjalan, Kekakuan,
Sesak / kelelahan saat aktivitas, Gangguan fungsi pasca cedera / operasi, Lainnya (wajib diisi teks).

#### Langkah 2: Lokasi Keluhan (Body Map)
* Peta tubuh interaktif depan/belakang dengan **35 area detail** (kiri/kanan), dapat memilih beberapa area sekaligus.
* Area detail dikelompokkan ke area PRD: Kepala/Leher, Bahu, Siku, Pergelangan tangan, Punggung, Pinggang, Panggul, Lutut,
  Pergelangan kaki, dan Area lain (dada, perut, paha, betis).
* **Wajib** jika keluhan mencakup Nyeri, Kelemahan otot, Kesulitan bergerak, Kekakuan, atau Gangguan pasca cedera/operasi;
  selain itu boleh dilewati.

#### Langkah 3: Karakteristik Keluhan
* **Selalu ditanyakan**: sejak kapan (atau "Tidak tahu"), tiba-tiba/bertahap, faktor pemberat (opsional), faktor
  peringan (opsional), apakah mengganggu aktivitas sehari-hari, catatan tambahan (opsional).
* **Menyesuaikan keluhan** (*dynamic questionnaire*):

| Keluhan dipilih | Pertanyaan tambahan |
| :-- | :-- |
| Nyeri | Skala nyeri 0–10 **per area** (atau satu nilai keseluruhan jika tanpa area), nyeri menjalar, gangguan tidur, sifat nyeri |
| Kekakuan | Apakah merasa kaku |
| Kelemahan otot | Kesemutan / baal |

* Skala nyeri 9–10 langsung menampilkan peringatan; jika tetap dikirim, program dihentikan (red flag otomatis).

#### Langkah 4: Red Flag Screening
Lihat [03-red-flag-screening.md](03-red-flag-screening.md).

#### Langkah 5: Kelompok Khusus
Sistem menampilkan semua kelompok yang berlaku (bisa lebih dari satu):

| Kondisi | Kelompok |
| :-- | :-- |
| Usia < 18 tahun | Screening tumbuh kembang & aktivitas |
| Kehamilan (perempuan) | Screening kehamilan & aktivitas aman |
| Usia ≥ 60 tahun | Screening risiko jatuh & fungsi dasar |
| Pasca operasi / cedera (profil) atau keluhan pasca cedera | Screening pasca operasi / cedera |
| Tidak ada di atas | Assessment sesuai domain |

CTA: **"Lanjut ke Identifikasi Domain Fisioterapi"**.

---

### 4. Kriteria Keberhasilan

| Kriteria | Status |
| :-- | :-- |
| Red flag hard stop memblokir program dan menampilkan halaman rujukan | ✅ |
| Body map dapat memilih satu/beberapa area dengan respons visual jelas | ✅ |
| Pertanyaan karakteristik menyesuaikan keluhan | ✅ |
| Pengguna terarah ke kelompok khusus yang sesuai | ✅ ditampilkan dan disimpan (lihat catatan) |

---

### 5. Keputusan Implementasi & Perbedaan dengan PRD Asli

* **Sub-screening per kelompok khusus tidak dibuat sebagai halaman terpisah.** Isinya dicakup oleh instrumen domain di
  Clinical Assessment: Geriatri → risiko jatuh (STEADI), TUG, Barthel; Pasca operasi → edema dan status luka; Pediatri →
  instrumen fisioterapis (Denver II/KPSP, GMFM).
* **Kehamilan belum memiliki screening khusus** maupun domain sendiri; pasien hamil mengikuti aturan domain umum.
  *Perlu keputusan klinis.*
* **Nyeri ≥ 9** dihentikan sejak langkah 3 tanpa menunggu pertanyaan red flag.
* Pertanyaan lanjutan ditentukan oleh `ScreeningRules`: titik awal untuk tinjauan klinis.

---

### 6. Lokasi Implementasi

* Halaman: `resources/views/livewire/pages/patient/screening.blade.php`, komponen `x-body-map`
* Aturan: `app/Support/Onboarding/ScreeningRules.php`, `BodyRegions.php`, `BodyMapGeometry.php`
* Data: tabel `screenings` (status `in_progress` / `stopped` / `completed`, `special_groups`)
