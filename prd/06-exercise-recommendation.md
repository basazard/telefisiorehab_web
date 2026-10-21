# Product Requirement Document (PRD)

## Modul Exercise / Telefisioterapi Recommendation – TeleFisioRehab

> **Status:** Terimplementasi (dengan fitur ditunda) · **PRD asli:** `Flow Sriyani/md/6_prd_modul_exercise_recommendation_telefisiorehab.md`

---

### 1. Ringkasan Produk & Tujuan

Berdasarkan kategori risiko, pengguna diarahkan ke salah satu dari tiga jalur layanan: **Self Management**, **Terbimbing**,
atau **Konsultasi / Rujukan**. Sistem menyusun program latihan yang dipersonalisasi (jenis, area tubuh, dosis), mencatat
latihan harian, dan menyediakan permintaan telekonsultasi/rujukan yang ditindaklanjuti staf klinik. Monitoring berkala
dijelaskan di [07-monitoring-reassessment.md](07-monitoring-reassessment.md).

---

### 2. Alur Pengguna (User Flow)

```
[Kategori risiko dari Modul 5]
   │
   ├─► RISIKO RENDAH: Self Management
   │      Edukasi + ergonomi (kartu infografis) ──► "Mulai program latihan mandiri"
   │      Latihan: peregangan, penguatan, aerobik ringan, relaksasi · monitoring tiap 4 minggu
   │
   ├─► RISIKO SEDANG: Terbimbing
   │      Edukasi nyeri & gaya hidup + telekonsultasi ──► "Mulai program latihan terstruktur"
   │      Latihan: ROM, penguatan (+ keseimbangan, pernapasan sesuai domain) · dosis lebih rendah
   │      Ditandai "Dipantau fisioterapis" · monitoring tiap 2 minggu
   │
   └─► RISIKO TINGGI: Konsultasi / Rujukan (TIDAK ADA LATIHAN MANDIRI)
          Tombol: Telekonsultasi fisioterapis · Rujukan ke faskes · Edukasi kondisi aman
   │
   ▼
Program Latihan ──► Log latihan harian ──► Monitoring (Modul 7)
```

---

### 3. Detail Kebutuhan Fitur (Sebagaimana Dibangun)

#### 3.1 Pemilihan & Dosis Latihan (DRAFT)
* **Pustaka bawaan 28 latihan** (teks langkah demi langkah dan catatan keamanan; video menyusul), dikelompokkan per jenis
  dan area tubuh (tungkai bawah, punggung bawah, bahu, leher, umum).
* Per jenis latihan dipilih **maksimal 2 latihan**, diutamakan yang sesuai area keluhan dari body map, selain itu latihan umum.
* Keseimbangan ditambahkan untuk Sedang bila domain Geriatri/Neuromuskular atau keluhan tungkai; pernapasan bila domain
  Kardiopulmonal/Geriatri/Neuromuskular.

| Satuan | Rendah (level 1) | Sedang (level 1) | Progresi per level (maks. 3) |
| :-- | :-- | :-- | :-- |
| Repetisi | 3 × 10 | 2 × 8 | +2 repetisi |
| Tahan (peregangan) | 3 × 30 dtk | 2 × 20 dtk | tetap |
| Tahan (keseimbangan) | 3 × 20 dtk | 2 × 15 dtk | +5 dtk |
| Aerobik | 15 menit | 10 menit | +5 menit |
| Relaksasi | 10 menit | 10 menit | tetap |

#### 3.2 Pemblokiran Keamanan
* **Risiko Tinggi tidak pernah mendapat program latihan mandiri**: diblokir di server, apa pun yang dikirim browser.
* **Pediatri** tidak mendapat program otomatis; program anak disusun langsung oleh fisioterapis (melalui telekonsultasi).
* Reassessment yang menghasilkan Risiko Tinggi **menghentikan program yang sedang berjalan**.

#### 3.3 Program Latihan & Log Latihan
* Halaman program: daftar latihan dengan dosis, langkah, dan catatan keamanan.
* **"Tandai selesai"** sekali per latihan per hari, dengan skala nyeri setelah latihan (opsional). Nyeri ≥ 7 memunculkan
  peringatan untuk menghentikan latihan dan mempertimbangkan reassessment.
* Tautan **reassessment lebih awal** jika ada keluhan baru atau kondisi memburuk.
* Program yang dijeda (menunggu reassessment) tidak dapat dicatat.

#### 3.4 Telekonsultasi & Rujukan
* Pasien mengirim **permintaan** telekonsultasi atau rujukan dengan keterangan opsional; satu permintaan terbuka per jenis.
* Status permintaan (Diproses / Ditangani) tampil di halaman rekomendasi dan dashboard.
* Staf (terapis, admin klinik, platform admin) menindaklanjuti di halaman **Konsultasi**: hanya pasien kliniknya, wajib
  catatan tindak lanjut minimal 10 karakter.

#### 3.5 Dashboard "Today" & Riwayat
* Dashboard: latihan selesai hari ini, jadwal monitoring berikutnya, **pengingat di dalam aplikasi** saat monitoring jatuh tempo,
  status permintaan, dan langkah berikutnya sesuai posisi pasien dalam alur.
* Riwayat: monitoring, 30 log latihan terakhir, dan assessment beserta kategori risikonya.

---

### 4. Kriteria Keberhasilan

| Kriteria | Status |
| :-- | :-- |
| Rendah/Sedang/Tinggi mendapat antarmuka rekomendasi yang tepat | ✅ |
| Risiko Tinggi dilarang ketat mendapat resep latihan mandiri | ✅ diblokir di server dan diuji |
| Log latihan dan kuesioner mencatat nyeri, fungsi, kepatuhan | ✅ (lihat Modul 7) |
| "Tidak ada perbaikan" memicu reassessment/konsultasi ulang | ✅ (diperluas di Modul 7) |

---

### 5. Keputusan Implementasi & Perbedaan dengan PRD Asli

* **Telekonsultasi** berupa permintaan yang dicatat dan ditangani staf; **video call belum dibangun**.
* **Pengingat** hanya di dalam aplikasi; **push notification belum dibangun**.
* **Video latihan** belum tersedia (`exercises.video_url` kosong); tautan video muncul otomatis bila diisi.
* Program Sedang **langsung aktif** dengan dosis lebih rendah dan ditandai dipantau fisioterapis (belum ada persetujuan terapis).
* **DRAFT:** pustaka latihan, dosis, progresi, interval monitoring, dan teks edukasi memerlukan persetujuan klinis.

---

### 6. Lokasi Implementasi

* Aturan: `app/Support/Onboarding/Intervention.php`, `ExerciseLibrary.php`, `Programs.php`; enum `ExerciseType`, `ProgramStatus`
* Halaman: `pages/patient/intervention`, `program`, `dashboard`, `history`; staf `pages/therapist/consultation-requests`
* Data: `exercises`, `programs`, `program_exercises`, `exercise_logs`, `consultation_requests`; `ExerciseSeeder`
