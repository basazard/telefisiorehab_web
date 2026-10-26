# Contoh Kasus untuk Review Klinis TeleFisioRehab

Versi aturan klinis: **2026.1-draft** · 28 kasus · dihitung oleh kode aplikasi yang sebenarnya (`php artisan clinical:vignettes`).

Setiap kasus menunjukkan masukan pasien dan hasil yang diberikan aplikasi. Mohon nilai apakah hasilnya sesuai secara klinis.
Instrumen hanya disebut nama dan skornya; isi pertanyaannya tidak dicantumkan.

## Kasus 01. Red flag: nyeri dada

**Masukan**

- Usia 45 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Punggung atas · nyeri tertinggi 5/10
- Red flag: chest_pain = Ya

**Hasil aplikasi**

- **Red flag: program DIHENTIKAN** (Nyeri dada atau dada terasa tertekan). Hanya staf klinik yang dapat membuka kembali.

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 02. Red flag: nyeri 9/10 tanpa jawaban "Ya"

**Masukan**

- Usia 38 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Punggung bawah · nyeri tertinggi 9/10
- Red flag: semua Tidak

**Hasil aplikasi**

- **Red flag: program DIHENTIKAN** (Skor nyeri sangat tinggi (9–10 dari 10)). Hanya staf klinik yang dapat membuka kembali.

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 03. Batas: nyeri 8/10 tanpa red flag (lanjut)

**Masukan**

- Usia 38 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Lutut kiri · nyeri tertinggi 8/10
- Red flag: semua Tidak
- LEFS: 20 item, skor 40/80

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: LEFS 40/80
- Keterbatasan fungsi atau risiko: Ya (Nyeri 8/10 (NRS dari screening awal); LEFS 40/80: keterbatasan fungsi tungkai bawah)
- Kategori risiko: **Risiko Tinggi** (Skala nyeri (NRS) 8/10 → Risiko Tinggi; LEFS – Fungsi tungkai bawah 40/80 → Risiko Sedang)
- Jalur: **Konsultasi / Rujukan** · evaluasi setiap 1 minggu
- Latihan mandiri: tidak diberikan; telekonsultasi atau rujukan.
- Edukasi utama: Posisi aman: "Pilih posisi yang paling nyaman dan tidak menambah nyeri; ganti posisi secara berkala."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 04. Muskuloskeletal lutut, fungsi baik (tanpa keterbatasan)

**Masukan**

- Usia 30 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Lutut kiri · nyeri tertinggi 2/10
- Red flag: semua Tidak
- LEFS: 20 item, skor 70/80

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: LEFS 70/80
- Keterbatasan fungsi atau risiko: Tidak
- Edukasi pencegahan, poin pertama: "Tetap aktif: usahakan aktivitas fisik sedang (mis. jalan cepat) total 150 menit per minggu."
- Kategori risiko: **Risiko Rendah** (Skala nyeri (NRS) 2/10 → Risiko Rendah; LEFS – Fungsi tungkai bawah 70/80 → Risiko Rendah)
- Jalur: **Self Management** · evaluasi setiap 4 minggu
- Program (level 1): Peregangan paha belakang (duduk) 3 × tahan 30 detik; Peregangan betis di dinding 3 × tahan 30 detik; Angkat tungkai lurus 3 × 10 repetisi; Duduk–berdiri dari kursi 3 × 10 repetisi; Jalan kaki santai 15 menit; Jalan di tempat 15 menit; Relaksasi otot progresif 10 menit
- Edukasi utama: Prinsip latihan: "Latihan rutin lebih penting daripada latihan berat. Usahakan setiap hari."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 05. Muskuloskeletal lutut, keterbatasan sedang

**Masukan**

- Usia 42 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri, Kekakuan · area: Lutut kanan · nyeri tertinggi 5/10
- Red flag: semua Tidak
- LEFS: 20 item, skor 45/80

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: LEFS 45/80
- Keterbatasan fungsi atau risiko: Ya (Nyeri 5/10 (NRS dari screening awal); LEFS 45/80: keterbatasan fungsi tungkai bawah)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 5/10 → Risiko Sedang; LEFS – Fungsi tungkai bawah 45/80 → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pompa pergelangan kaki 2 × 8 repetisi; Geser tumit (heel slide) 2 × 8 repetisi; Angkat tungkai lurus 2 × 8 repetisi; Duduk–berdiri dari kursi 2 × 8 repetisi; Memindahkan berat badan 2 × tahan 15 detik; Berdiri satu garis (tandem) 2 × tahan 15 detik
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 06. Muskuloskeletal punggung bawah, disabilitas berat

**Masukan**

- Usia 50 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Punggung bawah · nyeri tertinggi 7/10
- Red flag: semua Tidak
- ODI: 10 item, skor 50%

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: ODI 50%
- Keterbatasan fungsi atau risiko: Ya (Nyeri 7/10 (NRS dari screening awal); ODI 50%: disabilitas punggung bawah)
- Kategori risiko: **Risiko Tinggi** (Skala nyeri (NRS) 7/10 → Risiko Tinggi; ODI – Disabilitas punggung bawah 50% → Risiko Tinggi)
- Jalur: **Konsultasi / Rujukan** · evaluasi setiap 1 minggu
- Latihan mandiri: tidak diberikan; telekonsultasi atau rujukan.
- Edukasi utama: Posisi aman: "Pilih posisi yang paling nyaman dan tidak menambah nyeri; ganti posisi secara berkala."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 07. Muskuloskeletal bahu

**Masukan**

- Usia 55 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Bahu kanan · nyeri tertinggi 3/10
- Red flag: semua Tidak
- SPADI: 13 item, skor 40%

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: SPADI 40%
- Keterbatasan fungsi atau risiko: Ya (SPADI 40%: nyeri/disabilitas bahu)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 3/10 → Risiko Rendah; SPADI – Nyeri & disabilitas bahu 40% → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pendulum bahu 2 × 8 repetisi; Jari merambat di dinding 2 × 8 repetisi; Push-up ke dinding 2 × 8 repetisi
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 08. Muskuloskeletal leher (PSFS)

**Masukan**

- Usia 33 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Kekakuan · area: Leher · nyeri tertinggi 3/10
- Red flag: semua Tidak
- PSFS: 2 aktivitas, rata-rata 5.5/10

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: PSFS 5.5/10
- Keterbatasan fungsi atau risiko: Ya (PSFS rata-rata 5.5/10: aktivitas penting masih terganggu)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 3/10 → Risiko Rendah; PSFS – Aktivitas yang terganggu 5.5/10 → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Putar kepala perlahan 2 × 8 repetisi; Tarik dagu (chin tuck) 2 × 8 repetisi
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 09. Neuromuskular (gangguan neurologis pada profil)

**Masukan**

- Usia 58 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: Gangguan neurologis
- Keluhan: Kelemahan otot, Kesulitan berjalan · area: Paha depan kiri · nyeri tertinggi 2/10
- Red flag: semua Tidak
- Barthel Index: 10 item, skor 85/100
- TUG: 14 detik

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Neuromuskular** (Ada gangguan neurologis pada profil)
- Instrumen: Barthel Index 85/100; TUG 14 detik
- Keterbatasan fungsi atau risiko: Ya (Barthel Index 85/100: butuh bantuan dalam aktivitas sehari-hari; TUG 14 detik: risiko jatuh / mobilitas menurun)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 2/10 → Risiko Rendah; Barthel Index – Aktivitas sehari-hari 85/100 → Risiko Sedang; Timed Up and Go (TUG) 14 detik → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pernapasan diafragma 2 × 8 repetisi; Pursed-lip breathing 2 × 8 repetisi; Pompa pergelangan kaki 2 × 8 repetisi; Geser tumit (heel slide) 2 × 8 repetisi; Angkat tungkai lurus 2 × 8 repetisi; Duduk–berdiri dari kursi 2 × 8 repetisi; Memindahkan berat badan 2 × tahan 15 detik; Berdiri satu garis (tandem) 2 × tahan 15 detik
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 10. Kardiopulmonal ringan (tanpa keterbatasan)

**Masukan**

- Usia 40 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Sesak / kelelahan saat aktivitas · area: tidak spesifik · nyeri tertinggi 0/10
- Red flag: semua Tidak
- mMRC: Grade 1
- 1-minute sit to stand: 24 kali · Borg 3/10
- Tanda vital: Dalam batas normal

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Kardiopulmonal** (Keluhan sesak / kelelahan saat aktivitas)
- Instrumen: mMRC Grade 1; 1-minute sit to stand 24 kali · Borg 3/10; Tanda vital Dalam batas normal
- Keterbatasan fungsi atau risiko: Tidak
- Edukasi pencegahan, poin pertama: "Tetap aktif: usahakan aktivitas fisik sedang (mis. jalan cepat) total 150 menit per minggu."
- Kategori risiko: **Risiko Rendah** (Skala nyeri (NRS) 0/10 → Risiko Rendah; Modified MRC Dyspnea Scale Grade 1 → Risiko Rendah; 1-minute sit to stand + Borg Scale 24 kali · Borg 3/10 → Risiko Rendah; Tanda vital saat istirahat Dalam batas normal → Risiko Rendah)
- Jalur: **Self Management** · evaluasi setiap 4 minggu
- Program (level 1): Meraih ke atas 3 × tahan 30 detik; Angkat tungkai lurus 3 × 10 repetisi; Duduk–berdiri dari kursi 3 × 10 repetisi; Jalan kaki santai 15 menit; Jalan di tempat 15 menit; Relaksasi otot progresif 10 menit
- Edukasi utama: Prinsip latihan: "Latihan rutin lebih penting daripada latihan berat. Usahakan setiap hari."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 11. Kardiopulmonal dengan SpO2 88 % (darurat)

**Masukan**

- Usia 66 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: Penyakit paru
- Keluhan: Sesak / kelelahan saat aktivitas · area: tidak spesifik · nyeri tertinggi 0/10
- Red flag: semua Tidak
- mMRC: Grade 3
- 1-minute sit to stand: 10 kali · Borg 7/10
- Tanda vital: SpO2 88%, nadi 104/menit, napas 22/menit

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Screening risiko jatuh & fungsi dasar
- Domain: **Kardiopulmonal** (Keluhan sesak / kelelahan saat aktivitas)
- Instrumen: mMRC Grade 3; 1-minute sit to stand 10 kali · Borg 7/10; Tanda vital SpO2 88%, nadi 104/menit, napas 22/menit
- Keterbatasan fungsi atau risiko: Ya (mMRC grade 3: sesak membatasi aktivitas; Sit to stand 10 kali, Borg 7/10: toleransi aktivitas menurun; Tanda vital saat istirahat di luar batas normal: SpO2 88%, nadi 104/menit, napas 22/menit)
- Peringatan untuk pasien: Saturasi oksigen di bawah 90%. Segera hubungi tenaga kesehatan atau fasilitas kesehatan terdekat.
- Kategori risiko: **Risiko Tinggi** (Skala nyeri (NRS) 0/10 → Risiko Rendah; Modified MRC Dyspnea Scale Grade 3 → Risiko Tinggi; 1-minute sit to stand + Borg Scale 10 kali · Borg 7/10 → Risiko Tinggi; Tanda vital saat istirahat SpO2 88%, nadi 104/menit, napas 22/menit → Risiko Tinggi)
- **Kegawatdaruratan: program DIHENTIKAN seperti red flag** (Saturasi oksigen (SpO2) di bawah 90%).

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 12. Pediatrik 8 tahun

**Masukan**

- Usia 8 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Lutut kiri · nyeri tertinggi 3/10
- Red flag: semua Tidak

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Screening tumbuh kembang & aktivitas
- Domain: **Pediatri** (Usia di bawah 18 tahun)
- Instrumen: tidak ada (pemeriksaan fisioterapis)
- Keterbatasan fungsi atau risiko: Ya (Penilaian perkembangan memerlukan pemeriksaan fisioterapis)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 3/10 → Risiko Rendah; Penilaian fisioterapis Belum dilakukan → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Latihan mandiri: tidak dibuat sistem; program disusun fisioterapis.
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 13. Geriatri, risiko jatuh sedang

**Masukan**

- Usia 72 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Gangguan keseimbangan, Kesulitan berjalan · area: tidak spesifik · nyeri tertinggi 2/10
- Red flag: semua Tidak
- Checklist risiko jatuh: 12 item, skor 5/14
- TUG: 13 detik
- Barthel Index: 10 item, skor 95/100

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Screening risiko jatuh & fungsi dasar
- Domain: **Geriatri** (Usia 60 tahun ke atas)
- Instrumen: Checklist risiko jatuh 5/14; TUG 13 detik; Barthel Index 95/100
- Keterbatasan fungsi atau risiko: Ya (Skor risiko jatuh 5/14: berisiko jatuh; TUG 13 detik: risiko jatuh / mobilitas menurun; Barthel Index 95/100: butuh bantuan dalam aktivitas sehari-hari)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 2/10 → Risiko Rendah; Penilaian risiko jatuh 5/14 → Risiko Sedang; Timed Up and Go (TUG) 13 detik → Risiko Sedang; Barthel Index – Aktivitas sehari-hari 95/100 → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pernapasan diafragma 2 × 8 repetisi; Pursed-lip breathing 2 × 8 repetisi; Pompa pergelangan kaki 2 × 8 repetisi; Geser tumit (heel slide) 2 × 8 repetisi; Angkat tungkai lurus 2 × 8 repetisi; Duduk–berdiri dari kursi 2 × 8 repetisi; Memindahkan berat badan 2 × tahan 15 detik; Berdiri satu garis (tandem) 2 × tahan 15 detik
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 14. Geriatri, TUG tidak dapat dilakukan

**Masukan**

- Usia 80 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Kesulitan berjalan · area: tidak spesifik · nyeri tertinggi 3/10
- Red flag: semua Tidak
- Checklist risiko jatuh: 12 item, skor 8/14
- TUG: Tidak dapat dilakukan
- Barthel Index: 10 item, skor 70/100

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Screening risiko jatuh & fungsi dasar
- Domain: **Geriatri** (Usia 60 tahun ke atas)
- Instrumen: Checklist risiko jatuh 8/14; TUG Tidak dapat dilakukan; Barthel Index 70/100
- Keterbatasan fungsi atau risiko: Ya (Skor risiko jatuh 8/14: berisiko jatuh; TUG tidak dapat dilakukan dengan aman: risiko jatuh / keterbatasan mobilitas; Barthel Index 70/100: butuh bantuan dalam aktivitas sehari-hari)
- Kategori risiko: **Risiko Tinggi** (Skala nyeri (NRS) 3/10 → Risiko Rendah; Penilaian risiko jatuh 8/14 → Risiko Tinggi; Timed Up and Go (TUG) Tidak dapat dilakukan → Risiko Tinggi; Barthel Index – Aktivitas sehari-hari 70/100 → Risiko Sedang)
- Jalur: **Konsultasi / Rujukan** · evaluasi setiap 1 minggu
- Latihan mandiri: tidak diberikan; telekonsultasi atau rujukan.
- Edukasi utama: Posisi aman: "Pilih posisi yang paling nyaman dan tidak menambah nyeri; ganti posisi secara berkala."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 15. Pasca operasi lutut, bengkak sedang

**Masukan**

- Usia 47 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: Pasca operasi
- Keluhan: Gangguan fungsi pasca cedera / operasi, Nyeri · area: Lutut kiri · nyeri tertinggi 4/10
- Red flag: semua Tidak
- LEFS: 20 item, skor 40/80
- Edema: Sedang · selisih 3 cm
- Luka: Masih ada luka, bersih dan kering

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Screening pasca operasi / cedera
- Domain: **Post Operasi / Cedera** (Keluhan gangguan fungsi pasca cedera / operasi)
- Instrumen: LEFS 40/80; Edema Sedang · selisih 3 cm; Luka Masih ada luka, bersih dan kering
- Keterbatasan fungsi atau risiko: Ya (Nyeri 4/10 (NRS dari screening awal); LEFS 40/80: keterbatasan fungsi tungkai bawah; Bengkak: Sedang · selisih 3 cm)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 4/10 → Risiko Sedang; LEFS – Fungsi tungkai bawah 40/80 → Risiko Sedang; Bengkak (edema) Sedang · selisih 3 cm → Risiko Sedang; Status luka Masih ada luka, bersih dan kering → Risiko Rendah)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pompa pergelangan kaki 2 × 8 repetisi; Geser tumit (heel slide) 2 × 8 repetisi; Angkat tungkai lurus 2 × 8 repetisi; Duduk–berdiri dari kursi 2 × 8 repetisi; Memindahkan berat badan 2 × tahan 15 detik; Berdiri satu garis (tandem) 2 × tahan 15 detik
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 16. Pasca cedera dengan tanda infeksi luka (darurat)

**Masukan**

- Usia 29 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: Cedera / trauma
- Keluhan: Gangguan fungsi pasca cedera / operasi · area: Pergelangan kaki / kaki kanan · nyeri tertinggi 4/10
- Red flag: semua Tidak
- LEFS: 20 item, skor 55/80
- Edema: Ringan
- Luka: Ada kemerahan, terasa panas, bengkak bertambah, atau keluar cairan/nanah

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Screening pasca operasi / cedera
- Domain: **Post Operasi / Cedera** (Keluhan gangguan fungsi pasca cedera / operasi)
- Instrumen: LEFS 55/80; Edema Ringan; Luka Ada kemerahan, terasa panas, bengkak bertambah, atau keluar cairan/nanah
- Keterbatasan fungsi atau risiko: Ya (Nyeri 4/10 (NRS dari screening awal); LEFS 55/80: keterbatasan fungsi tungkai bawah; Tanda infeksi pada luka)
- Peringatan untuk pasien: Luka menunjukkan tanda infeksi. Hubungi dokter atau fasilitas kesehatan sebelum mulai latihan.
- Kategori risiko: **Risiko Tinggi** (Skala nyeri (NRS) 4/10 → Risiko Sedang; LEFS – Fungsi tungkai bawah 55/80 → Risiko Sedang; Bengkak (edema) Ringan → Risiko Rendah; Status luka Ada kemerahan, terasa panas, bengkak bertambah, atau keluar cairan/nanah → Risiko Tinggi)
- **Kegawatdaruratan: program DIHENTIKAN seperti red flag** (Tanda infeksi pada luka operasi / cedera).

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 17. Olahraga (atlet), lutut

**Masukan**

- Usia 24 tahun, laki-laki, pekerjaan: Atlet
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Lutut kanan · nyeri tertinggi 1/10
- Red flag: semua Tidak
- LEFS: 20 item, skor 72/80
- PSFS: 1 aktivitas, rata-rata 8/10

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Olahraga** (Aktivitas utama sebagai atlet)
- Instrumen: LEFS 72/80; PSFS 8/10
- Keterbatasan fungsi atau risiko: Tidak
- Edukasi pencegahan, poin pertama: "Tetap aktif: usahakan aktivitas fisik sedang (mis. jalan cepat) total 150 menit per minggu."
- Kategori risiko: **Risiko Rendah** (Skala nyeri (NRS) 1/10 → Risiko Rendah; LEFS – Fungsi tungkai bawah 72/80 → Risiko Rendah; PSFS – Aktivitas yang terganggu 8/10 → Risiko Rendah)
- Jalur: **Self Management** · evaluasi setiap 4 minggu
- Program (level 1): Peregangan paha belakang (duduk) 3 × tahan 30 detik; Peregangan betis di dinding 3 × tahan 30 detik; Angkat tungkai lurus 3 × 10 repetisi; Duduk–berdiri dari kursi 3 × 10 repetisi; Jalan kaki santai 15 menit; Jalan di tempat 15 menit; Relaksasi otot progresif 10 menit
- Edukasi utama: Prinsip latihan: "Latihan rutin lebih penting daripada latihan berat. Usahakan setiap hari."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 18. Kehamilan dengan nyeri punggung bawah

**Masukan**

- Usia 30 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: Kehamilan
- Keluhan: Nyeri · area: Punggung bawah · nyeri tertinggi 4/10
- Red flag: semua Tidak
- ODI: 10 item, skor 24%

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Screening kehamilan & aktivitas aman
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: ODI 24%
- Keterbatasan fungsi atau risiko: Ya (Nyeri 4/10 (NRS dari screening awal); ODI 24%: disabilitas punggung bawah)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 4/10 → Risiko Sedang; ODI – Disabilitas punggung bawah 24% → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pelvic tilt 2 × 8 repetisi; Cat–camel 2 × 8 repetisi; Bridging (angkat panggul) 2 × 8 repetisi; Bird dog 2 × 8 repetisi
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

> Catatan: Kehamilan belum punya screening atau domain sendiri: pasien diproses sebagai muskuloskeletal biasa.

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 19. Lisensi: ODI dimatikan, punggung bawah

**Masukan**

- Switch lisensi: ODI dimatikan
- Usia 50 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Punggung bawah · nyeri tertinggi 4/10
- Red flag: semua Tidak
- PSFS: 2 aktivitas, rata-rata 5.5/10

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: PSFS 5.5/10
- Keterbatasan fungsi atau risiko: Ya (Nyeri 4/10 (NRS dari screening awal); PSFS rata-rata 5.5/10: aktivitas penting masih terganggu)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 4/10 → Risiko Sedang; PSFS – Aktivitas yang terganggu 5.5/10 → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pelvic tilt 2 × 8 repetisi; Cat–camel 2 × 8 repetisi; Bridging (angkat panggul) 2 × 8 repetisi; Bird dog 2 × 8 repetisi
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 20. Lisensi: SPADI dimatikan, bahu

**Masukan**

- Switch lisensi: SPADI dimatikan
- Usia 55 tahun, perempuan, pekerjaan: Pekerja kantor
- Kondisi khusus: tidak ada
- Keluhan: Nyeri · area: Bahu kanan · nyeri tertinggi 3/10
- Red flag: semua Tidak
- PSFS: 1 aktivitas, rata-rata 6/10

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Assessment sesuai domain
- Domain: **Muskuloskeletal** (Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi)
- Instrumen: PSFS 6/10
- Keterbatasan fungsi atau risiko: Ya (PSFS rata-rata 6/10: aktivitas penting masih terganggu)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 3/10 → Risiko Rendah; PSFS – Aktivitas yang terganggu 6/10 → Risiko Sedang)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pendulum bahu 2 × 8 repetisi; Jari merambat di dinding 2 × 8 repetisi; Push-up ke dinding 2 × 8 repetisi
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 21. Lisensi: Borg CR10 dimatikan, kardiopulmonal

**Masukan**

- Switch lisensi: Borg CR10 dimatikan
- Usia 62 tahun, laki-laki, pekerjaan: Pekerja kantor
- Kondisi khusus: Penyakit paru
- Keluhan: Sesak / kelelahan saat aktivitas · area: tidak spesifik · nyeri tertinggi 0/10
- Red flag: semua Tidak
- mMRC: Grade 1
- 1-minute sit to stand: 18 kali
- Tanda vital: Dalam batas normal

**Hasil aplikasi**

- Red flag: tidak ada, lanjut.
- Kelompok khusus: Screening risiko jatuh & fungsi dasar
- Domain: **Kardiopulmonal** (Keluhan sesak / kelelahan saat aktivitas)
- Instrumen: mMRC Grade 1; 1-minute sit to stand 18 kali; Tanda vital Dalam batas normal
- Keterbatasan fungsi atau risiko: Ya (Sit to stand 18 kali: toleransi aktivitas menurun)
- Kategori risiko: **Risiko Sedang** (Skala nyeri (NRS) 0/10 → Risiko Rendah; Modified MRC Dyspnea Scale Grade 1 → Risiko Rendah; 1-minute sit to stand 18 kali → Risiko Sedang; Tanda vital saat istirahat Dalam batas normal → Risiko Rendah)
- Jalur: **Terbimbing** · evaluasi setiap 2 minggu
- Program (level 1): Pernapasan diafragma 2 × 8 repetisi; Pursed-lip breathing 2 × 8 repetisi; Pompa pergelangan kaki 2 × 8 repetisi; Geser tumit (heel slide) 2 × 8 repetisi; Angkat tungkai lurus 2 × 8 repetisi; Duduk–berdiri dari kursi 2 × 8 repetisi
- Edukasi utama: Manajemen nyeri: "Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam."

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 22. Lisensi: ODI dimatikan saat monitoring (baseline dengan ODI)

**Masukan**

- Switch lisensi: ODI dimatikan
- Program terbimbing (Risiko Sedang), muskuloskeletal punggung bawah, level 1, kepatuhan 80 %, 0 minggu tanpa perbaikan
- Awal: nyeri 6/10; ODI: 10 item, skor 50%
- Sekarang: nyeri 4/10; ODI: dinonaktifkan (lisensi); perubahan menurut pasien: lebih baik

**Hasil aplikasi**

- Perbandingan: Skala nyeri (NRS) 6/10 → 4/10 (bermakna, membaik); ODI – Disabilitas punggung bawah 50% → dinonaktifkan (lisensi) (tidak bermakna)
- Kategori risiko sekarang: Risiko Sedang
- Keputusan: **Lanjutkan program (progresif)**
- Tindak lanjut program: level 1 → 2 dan satu variasi latihan

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 23. Monitoring: membaik

**Masukan**

- Program terbimbing (Risiko Sedang), muskuloskeletal lutut, level 1, kepatuhan 80 %, 0 minggu tanpa perbaikan
- Awal: nyeri 5/10; LEFS: 20 item, skor 45/80
- Sekarang: nyeri 3/10; LEFS: 20 item, skor 60/80; perubahan menurut pasien: lebih baik

**Hasil aplikasi**

- Perbandingan: Skala nyeri (NRS) 5/10 → 3/10 (bermakna, membaik); LEFS – Fungsi tungkai bawah 45/80 → 60/80 (bermakna, membaik)
- Kategori risiko sekarang: Risiko Rendah
- Keputusan: **Lanjutkan program (progresif)**
- Tindak lanjut program: level 1 → 2 dan satu variasi latihan

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 24. Monitoring: sama saja, kepatuhan baik

**Masukan**

- Program terbimbing (Risiko Sedang), muskuloskeletal lutut, level 2, kepatuhan 70 %, 0 minggu tanpa perbaikan
- Awal: nyeri 5/10; LEFS: 20 item, skor 45/80
- Sekarang: nyeri 5/10; LEFS: 20 item, skor 47/80; perubahan menurut pasien: sama saja

**Hasil aplikasi**

- Perbandingan: Skala nyeri (NRS) 5/10 → 5/10 (tidak bermakna); LEFS – Fungsi tungkai bawah 45/80 → 47/80 (tidak bermakna)
- Kategori risiko sekarang: Risiko Sedang
- Keputusan: **Modifikasi program**
- Tindak lanjut program: level 2 → 1 (dosis lebih ringan)

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 25. Monitoring: sama saja, kepatuhan rendah

**Masukan**

- Program terbimbing (Risiko Sedang), muskuloskeletal lutut, level 2, kepatuhan 30 %, 0 minggu tanpa perbaikan
- Awal: nyeri 5/10; LEFS: 20 item, skor 45/80
- Sekarang: nyeri 5/10; LEFS: 20 item, skor 46/80; perubahan menurut pasien: sama saja

**Hasil aplikasi**

- Perbandingan: Skala nyeri (NRS) 5/10 → 5/10 (tidak bermakna); LEFS – Fungsi tungkai bawah 45/80 → 46/80 (tidak bermakna)
- Kategori risiko sekarang: Risiko Sedang
- Keputusan: **Modifikasi program**
- Tindak lanjut program: level tetap 2 (kepatuhan di bawah 50 %, tinjau hambatan dulu)

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 26. Monitoring: tanpa perbaikan 4 minggu

**Masukan**

- Program terbimbing (Risiko Sedang), muskuloskeletal lutut, level 1, kepatuhan 80 %, 4 minggu tanpa perbaikan
- Awal: nyeri 5/10; LEFS: 20 item, skor 45/80
- Sekarang: nyeri 5/10; LEFS: 20 item, skor 46/80; perubahan menurut pasien: sama saja

**Hasil aplikasi**

- Perbandingan: Skala nyeri (NRS) 5/10 → 5/10 (tidak bermakna); LEFS – Fungsi tungkai bawah 45/80 → 46/80 (tidak bermakna)
- Kategori risiko sekarang: Risiko Sedang
- Keputusan: **Reassessment & konsultasi ulang**
- Tindak lanjut program: program dijeda sampai reassessment

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 27. Monitoring: memburuk drastis

**Masukan**

- Program terbimbing (Risiko Sedang), muskuloskeletal lutut, level 1, kepatuhan 80 %, 0 minggu tanpa perbaikan
- Awal: nyeri 5/10; LEFS: 20 item, skor 45/80
- Sekarang: nyeri 8/10; LEFS: 20 item, skor 30/80; perubahan menurut pasien: lebih buruk

**Hasil aplikasi**

- Perbandingan: Skala nyeri (NRS) 5/10 → 8/10 (bermakna, memburuk); LEFS – Fungsi tungkai bawah 45/80 → 30/80 (bermakna, memburuk)
- Kategori risiko sekarang: Risiko Tinggi
- Keputusan: **Pertimbangkan rujukan medis**
- Tindak lanjut program: program dijeda sampai reassessment

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________

## Kasus 28. Monitoring: tujuan tercapai

**Masukan**

- Program terbimbing (Risiko Sedang), muskuloskeletal lutut, level 2, kepatuhan 80 %, 0 minggu tanpa perbaikan
- Awal: nyeri 5/10; LEFS: 20 item, skor 45/80
- Sekarang: nyeri 1/10; LEFS: 20 item, skor 72/80; perubahan menurut pasien: lebih baik

**Hasil aplikasi**

- Perbandingan: Skala nyeri (NRS) 5/10 → 1/10 (bermakna, membaik); LEFS – Fungsi tungkai bawah 45/80 → 72/80 (bermakna, membaik)
- Kategori risiko sekarang: Risiko Rendah
- Keputusan: **Tujuan tercapai: Maintenance Program**
- Tindak lanjut program: program berakhir, Maintenance Program dimulai (level 2, evaluasi setiap 4 minggu)

[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________
