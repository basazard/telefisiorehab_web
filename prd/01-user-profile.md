# Product Requirement Document (PRD)

## Modul User Profile – TeleFisioRehab

> **Status:** Terimplementasi · **PRD asli:** `Flow Sriyani/md/1_prd_modul_user_profile_telefisiorehab.md`

---

### 1. Ringkasan Produk & Tujuan

Modul **User Profile** mengumpulkan data awal pengguna (identitas, data fisik, pekerjaan dan aktivitas, kondisi khusus, dan
tujuan) untuk menentukan modul layanan berdasarkan usia (Pediatri, Dewasa, Geriatri) dan mempersonalisasi alur berikutnya.
Profil wajib lengkap sebelum pengguna dapat memulai *Initial Screening*.

---

### 2. Alur Pengguna (User Flow)

```
[Registrasi akun] ──► (otomatis dibuat data pasien kosong)
   │
   ▼
1. Identitas (Tanggal lahir → usia dihitung otomatis, Jenis kelamin)
   │
   ▼
2. Data Fisik (Tinggi, Berat → BMI otomatis + kategori)
   │
   ▼
3. Pekerjaan & Aktivitas (Pekerjaan utama, Jam aktivitas per hari)
   │
   ▼
4. Kondisi Khusus (Ya → pilih ≥ 1 kondisi; Tidak → lanjut)
   │
   ▼
5. Tujuan (1–3 pilihan)
   │
   ▼
6. Tinjau & Simpan (ringkasan + modul layanan berdasarkan usia)
   │
   ▼
[Lanjut ke Initial Screening]
```

---

### 3. Detail Kebutuhan Fitur (Sebagaimana Dibangun)

#### Langkah 1: Identitas
* **Nama**: diambil dari akun pendaftaran.
* **Tanggal lahir** (wajib): tidak boleh di masa depan, maksimal 119 tahun lalu. **Usia dihitung otomatis** dari tanggal lahir.
* **Jenis kelamin** (wajib): Laki-laki / Perempuan.

#### Langkah 2: Data Fisik
* **Tinggi badan**: 30–250 cm. **Berat badan**: 2–300 kg.
* **BMI** dihitung langsung saat diketik: `BMI = berat (kg) / (tinggi (m))²`, dibulatkan 1 desimal, dengan kategori:
  Berat badan kurang (< 18,5), Normal (18,5–24,9), Berat badan berlebih (25–29,9), Obesitas (≥ 30).

#### Langkah 3: Pekerjaan & Aktivitas
* **Pekerjaan / aktivitas utama** (pilih satu): Pekerja kantor, Pekerja lapangan / fisik, Tenaga kesehatan, Guru, Pengemudi,
  Pelajar / Mahasiswa, Ibu rumah tangga, Pensiunan, Atlet, Lainnya.
* **Jam aktivitas per hari**: 0–24.

#### Langkah 4: Modul Berdasarkan Usia (otomatis)
| Usia | Modul | Fokus |
| :-- | :-- | :-- |
| 0–17 tahun | Modul Pediatri | Tumbuh kembang dan aktivitas |
| 18–59 tahun | Modul Dewasa | Screening umum |
| ≥ 60 tahun | Modul Geriatri | Mobilitas, keseimbangan, risiko jatuh |

#### Langkah 5: Kondisi Khusus
* Pertanyaan *"Apakah Anda memiliki kondisi khusus?"*; jika **Ya**, minimal satu kondisi wajib dipilih (boleh lebih dari satu):
  Pasca operasi, Cedera / trauma, Kehamilan, Penyakit jantung, Penyakit paru, Diabetes, Hipertensi, Gangguan neurologis,
  Gangguan muskuloskeletal, Lainnya (wajib diisi teks, maks. 150 karakter).
* **Kehamilan hanya dapat dipilih untuk jenis kelamin perempuan** (divalidasi di server).

#### Langkah 6: Tujuan
* Minimal 1, **maksimal 3** tujuan: Mengurangi nyeri, Meningkatkan kekuatan otot, Meningkatkan kelenturan / ROM,
  Meningkatkan keseimbangan, Meningkatkan kemampuan berjalan, Meningkatkan kebugaran, Pemulihan setelah cedera,
  Pemulihan setelah operasi, Meningkatkan aktivitas sehari-hari, Mencegah kekambuhan, Mempertahankan fungsi tubuh,
  Lainnya (wajib diisi teks).

#### Langkah 7: Validasi, Simpan & Navigasi
* Setiap langkah divalidasi sebelum lanjut (kelengkapan dan rentang nilai); seluruh data divalidasi ulang saat disimpan.
* Setelah disimpan, profil ditandai lengkap dan pengguna diarahkan ke **Initial Screening**.
* Profil dapat diperbarui kemudian dari halaman Profil Akun ("Perbarui data kesehatan").

---

### 4. Kriteria Keberhasilan

| Kriteria | Status |
| :-- | :-- |
| BMI dihitung real-time saat tinggi dan berat diisi | ✅ |
| Pengguna otomatis dialokasikan ke modul Pediatri/Dewasa/Geriatri berdasarkan usia | ✅ |
| Sistem mencegah lebih dari 3 tujuan | ✅ |
| Data tersimpan sebelum pindah ke Initial Screening; halaman lain terkunci sampai profil lengkap | ✅ |

---

### 5. Keputusan Implementasi & Perbedaan dengan PRD Asli

* **Tanggal lahir menggantikan input usia**: usia selalu akurat dan ikut bertambah, tidak perlu diperbarui manual.
* **"Nama / ID"** tidak diinput ulang; memakai nama akun.
* **Data pasien kosong** dibuat otomatis saat registrasi; semua akun baru berperan sebagai pasien.

---

### 6. Lokasi Implementasi

* Halaman: `resources/views/livewire/pages/patient/profile-setup.blade.php`
* Validasi: `app/Livewire/Forms/ProfileForm.php`; enum `Gender`, `SpecialCondition`, `Goal`, `ServiceModule`
* Gerbang profil lengkap: middleware `profile.complete` (`EnsureProfileComplete`)
