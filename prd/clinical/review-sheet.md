# Lembar Review Parameter Klinis TeleFisioRehab

Versi aturan klinis: **2026.1-draft** · jumlah parameter: **163** · dibuat otomatis dari `prd/clinical/parameters.json` (`php artisan clinical:review-sheet`).

## 1. Pengantar untuk reviewer

**Apa yang dilakukan aplikasi.** TeleFisioRehab adalah aplikasi fisioterapi di rumah. Pasien mengisi profil, screening keluhan dan pertanyaan red flag,
lalu kuesioner dan tes mandiri sesuai domain fisioterapi. Dari jawaban itu aplikasi menentukan kategori risiko (Rendah, Sedang, Tinggi), jalur layanan,
program latihan dengan dosisnya, dan setiap beberapa minggu menilai perkembangan (monitoring) untuk melanjutkan, mengubah, menjeda, atau mengakhiri program.
Fisioterapis klinik dapat meninjau dan mengubah program.

**Apa yang tidak dilakukan aplikasi.** Aplikasi tidak mendiagnosis, tidak menggantikan pemeriksaan langsung, dan tidak memberi latihan mandiri kepada
pasien Risiko Tinggi atau pasien anak. Satu jawaban "Ya" pada red flag atau nyeri sangat tinggi menghentikan program sampai staf klinik membukanya kembali.

**Yang diminta dari Anda.** Semua nilai dan aturan di bawah masih **DRAFT**. Mohon nilai setiap baris: setuju, ubah (tulis nilai baru), atau hapus.
Kolom "Alasan dan sumber" hanya berisi apa yang tercatat di dokumen atau kode; "tidak diketahui, perlu diisi" berarti sumbernya belum ada,
dan Anda boleh menuliskannya di kolom komentar. Untuk aturan (bukan angka), lihat juga contoh kasus di `prd/clinical/vignettes.md`.
Isi instrumen berlisensi (ODI, SPADI, Borg CR10 dan lainnya) tidak dicantumkan; instrumen hanya disebut namanya.

**Cara menjawab.** Centang satu pilihan di kolom "Keputusan". Bila "Ubah", tulis nilai baru. Tulis alasan atau sumber di kolom komentar.
Hasil akan disalin ke `prd/clinical/decisions.csv` dan hanya keputusan tertulis yang diterapkan ke aplikasi.

### Tanda tangan reviewer

| | |
| --- | --- |
| Nama | |
| Profesi | |
| Nomor STR / SIP | |
| Tanggal review | |
| Modul yang direview | |
| Tanda tangan | |

Pernyataan: *Saya telah meninjau parameter pada modul yang saya tandai di atas dan keputusan saya tercatat pada lembar ini.*

## 2. Red flag (tanda bahaya)

Reviewer yang disarankan: Dokter (mis. spesialis rehabilitasi medik) dan fisioterapis · 18 parameter · 18 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-RF-01 | Pertanyaan red flag "chest_pain" | Nyeri dada atau dada terasa tertekan? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-02 | Pertanyaan red flag "severe_breathlessness" | Sesak napas berat? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-03 | Pertanyaan red flag "fainting" | Pingsan atau hilang kesadaran? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Dibawa dari aplikasi lama (bukan dari PRD 03); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-04 | Pertanyaan red flag "sudden_weakness" | Kelemahan mendadak pada lengan atau tungkai? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-05 | Pertanyaan red flag "speech_difficulty" | Gangguan bicara mendadak (pelo / sulit berbicara)? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-06 | Pertanyaan red flag "incontinence" | Hilang kontrol BAK / BAB secara mendadak? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-07 | Pertanyaan red flag "saddle_numbness" | Baal di area selangkangan atau pangkal paha bagian dalam? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Dibawa dari aplikasi lama (bukan dari PRD 03); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-08 | Pertanyaan red flag "high_fever" | Demam tinggi (di atas 38,5 °C)? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-09 | Pertanyaan red flag "major_trauma" | Trauma berat atau kecelakaan baru-baru ini? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-10 | Pertanyaan red flag "suspected_fracture" | Dugaan patah tulang (bentuk berubah atau tidak bisa menumpu berat badan)? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Dibawa dari aplikasi lama (bukan dari PRD 03); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-11 | Pertanyaan red flag "uncontrolled_bleeding" | Perdarahan yang tidak berhenti? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Dibawa dari aplikasi lama (bukan dari PRD 03); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-12 | Pertanyaan red flag "sudden_severe_headache" | Sakit kepala hebat mendadak ("terburuk seumur hidup")? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Dibawa dari aplikasi lama (bukan dari PRD 03); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-13 | Pertanyaan red flag "unexplained_weight_loss" | Penurunan berat badan cepat tanpa sebab yang jelas? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Dibawa dari aplikasi lama (bukan dari PRD 03); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-14 | Pertanyaan red flag "night_pain" | Nyeri malam hari yang membuat sulit tidur dan tidak membaik? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Dibawa dari aplikasi lama (bukan dari PRD 03); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-15 | Pertanyaan red flag "severe_pain" | Nyeri sangat berat yang tidak tertahankan? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-16 | Pertanyaan red flag "other" | Ada tanda bahaya lain yang Anda rasakan? | Alasan: Satu jawaban "Ya" menghentikan program (hard stop).<br>Sumber: Menurut kode (RedFlags::FROM_PRD) berasal dari PRD 03; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-17 | Ambang nyeri yang menghentikan program | 9 (NRS 0–10) | Alasan: Nyeri pada atau di atas nilai ini dianggap red flag sendiri (komentar kode).<br>Sumber: Komentar kode: perlu persetujuan klinis; sumber tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RF-18 | Aturan hard stop: satu "Ya" atau nyeri ≥ ambang | Program berhenti bila ada satu jawaban "Ya" atau nyeri tertinggi ≥ ambang nyeri. | Alasan: Dinilai di server dari jawaban mentah.<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

**Pertanyaan terbuka**

- Apakah ambang nyeri 9 berlaku untuk setiap area keluhan, atau hanya untuk nyeri tertinggi (yang dipakai sekarang)?
- PRD 03 menyebut "gangguan penglihatan mendadak", yang belum ada di daftar. Perlu ditambahkan?
- Baal mendadak saat ini hanya ditanyakan untuk area selangkangan (saddle). Perlu pertanyaan baal mendadak yang lebih luas?
- Nomor darurat 112 dan ambulans 119: sudah benar untuk semua wilayah layanan?
- 7 pertanyaan berasal dari aplikasi lama, bukan dari PRD 03 (lihat kolom sumber). Dipertahankan?

## 3. Domain fisioterapi dan analisis keterbatasan

Reviewer yang disarankan: Fisioterapis · 14 parameter · 14 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-DA-01 | Urutan aturan pemilihan domain | Urutan: usia < 18 → Pediatrik; gangguan neurologis → Neuromuskular; keluhan sesak → Kardiopulmonal; penyakit jantung/paru → Kardiopulmonal; keluhan pasca cedera/operasi → Pasca operasi/cedera; riwayat operasi/cedera → Pasca operasi/cedera; usia ≥ 60 → Geriatri; pekerjaan "Atlet" → Olahraga; keluhan keseimbangan → Neuromuskular; selain itu → Muskuloskeletal. | Alasan: Aturan pertama yang cocok menang.<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-02 | Batas nyeri yang dihitung sebagai keterbatasan | 4 (NRS 0–10) | Alasan: NRS ≥ 4 (nyeri sedang atau lebih) dihitung sebagai keterbatasan (komentar kode).<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-03 | Area tubuh yang dihitung "tungkai bawah" | Hip; Thigh; Hamstring; Knee; Calf; Shin; Ankle; Heel | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-04 | Area tubuh yang dihitung "bahu" | Shoulder; Scapula | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-05 | Area tubuh yang dihitung "punggung bawah" | Lower Back | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-06 | Instrumen per area tubuh (muskuloskeletal) | Tungkai bawah → LEFS; punggung bawah → ODI; bahu → SPADI; area lain → PSFS. ODI atau SPADI yang dimatikan (lisensi) diganti PSFS. | Alasan: PSFS bila tidak ada instrumen khusus area yang cocok.<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-07 | Instrumen per domain | Muskuloskeletal: per area; Neuromuskular: Barthel, TUG; Kardiopulmonal: mMRC, 1-minute sit to stand, tanda vital; Pediatrik: tidak ada (pemeriksaan fisioterapis); Geriatri: checklist risiko jatuh, TUG, Barthel; Pasca operasi/cedera: per area + edema + status luka; Olahraga: per area + PSFS. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-08 | Keputusan "keterbatasan fungsi atau risiko?" | Ada keterbatasan bila nyeri ≥ batas nyeri atau salah satu instrumen di bawah cut-off; pediatrik selalu "ada keterbatasan". Ada keterbatasan → klasifikasi risiko; tidak ada → edukasi pencegahan. | Alasan: Tanpa instrumen (pediatrik) selalu dianggap ada keterbatasan, demi keamanan (komentar kode).<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-09 | Edukasi pencegahan, poin 1 | Tetap aktif: usahakan aktivitas fisik sedang (mis. jalan cepat) total 150 menit per minggu. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-10 | Edukasi pencegahan, poin 2 | Saat duduk lama, berdiri dan bergerak ringan setiap 30–60 menit. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-11 | Edukasi pencegahan, poin 3 | Lakukan pemanasan sebelum dan pendinginan sesudah olahraga. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-12 | Edukasi pencegahan, poin 4 | Naikkan beban atau durasi latihan secara bertahap, tidak lebih dari ±10% per minggu. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-13 | Edukasi pencegahan, poin 5 | Jaga postur saat mengangkat barang: tekuk lutut, dekatkan barang ke tubuh. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-DA-14 | Edukasi pencegahan, poin 6 | Hentikan latihan dan hubungi tenaga kesehatan bila muncul nyeri tajam, sesak berat, nyeri dada, atau pusing. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

**Pertanyaan terbuka**

- Apakah urutan aturan domain sudah tepat (aturan pertama yang cocok menang)?

## 4. Cut-off instrumen assessment

Reviewer yang disarankan: Fisioterapis · 21 parameter · 21 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-IN-01 | Cut-off LEFS (keterbatasan bila di bawah) | 60 (poin dari 80) | Alasan: LEFS < 60 dari 80.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-02 | Cut-off ODI (keterbatasan bila di atas) | 20 (%) | Alasan: ODI > 20 % (di atas "minimal disability").<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-03 | Cut-off SPADI (keterbatasan bila di atas) | 20 (%) | Alasan: SPADI > 20 %.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-04 | Cut-off PSFS rata-rata (keterbatasan bila di bawah) | 7 (poin dari 10) | Alasan: Rata-rata PSFS < 7 dari 10.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-05 | Cut-off Barthel Index (keterbatasan bila di bawah) | 100 (poin dari 100) | Alasan: Barthel < 100 = ada ketergantungan.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-06 | Cut-off checklist risiko jatuh (berisiko bila ≥) | 4 (poin dari 14) | Alasan: Checklist "Stay Independent" ≥ 4 = risiko jatuh.<br>Sumber: Komentar kode menyebut CDC STEADI; dokumen dan versi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-07 | Cut-off TUG (berisiko bila ≥) | 12 (detik) | Alasan: TUG ≥ 12 detik.<br>Sumber: Komentar kode menyebut CDC STEADI; dokumen dan versi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-08 | Cut-off mMRC (keterbatasan bila ≥) | 2 (grade 0–4) | Alasan: mMRC ≥ 2.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-09 | Cut-off 1-minute sit to stand (keterbatasan bila di bawah) | 20 (repetisi) | Alasan: < 20 repetisi dalam 1 menit.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-10 | Cut-off Borg CR10 setelah sit to stand (keterbatasan bila ≥) | 5 (skala 0–10) | Alasan: Borg ≥ 5 ("berat").<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-11 | Cut-off SpO2 saat istirahat (keterbatasan bila di bawah) | 95 (%) | Alasan: SpO2 < 95 %.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-12 | SpO2 darurat (pesan "hubungi tenaga kesehatan", dan red flag otomatis) | 90 (%) | Alasan: SpO2 < 90 %.<br>Sumber: Komentar kode: tampilkan "hubungi tenaga kesehatan"; sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-13 | Cut-off nadi saat istirahat (keterbatasan bila di atas) | 100 (denyut/menit) | Alasan: Nadi istirahat > 100.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-14 | Cut-off frekuensi napas saat istirahat (keterbatasan bila di atas) | 20 (napas/menit) | Alasan: Napas istirahat > 20.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-15 | Cut-off selisih lingkar bengkak (keterbatasan bila ≥) | 2 (cm) | Alasan: Selisih ≥ 2 cm dengan sisi sehat.<br>Sumber: Kode menyebut "published defaults"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-16 | Tes yang hanya boleh dilakukan dengan pendamping | tug; sts | Alasan: Tes mandiri hanya dengan orang di dekat pasien (komentar kode).<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-17 | Rumus skor tiap instrumen | LEFS: jumlah item (maks 80); ODI: % dari item yang dijawab; SPADI: % dari 130; PSFS: rata-rata aktivitas yang diisi; Barthel: jumlah (maks 100); checklist risiko jatuh: jumlah bobot "ya" (maks 14); TUG: detik, "tidak dapat" = keterbatasan; mMRC: grade; sit to stand: repetisi dan Borg (hanya repetisi bila Borg dimatikan), "tidak dapat" = keterbatasan; tanda vital: dibandingkan batas di atas; edema: tingkat sedang/berat atau selisih cm; luka: infeksi = keterbatasan dan peringatan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-18 | Rentang input yang diterima | Contoh: TUG 1–300 detik; sit to stand 0–100 repetisi; Borg 0–10; SpO2 50–100 %; nadi 30–220; napas 5–60; lingkar 5–200 cm. Satu item ODI boleh dikosongkan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-19 | Switch lisensi ODI, SPADI dan Borg CR10 | Aturan: ODI, SPADI dan Borg CR10 bisa dimatikan bila lisensinya ditolak (config/clinical.php); instrumen lain selalu aktif. Bila Borg dimatikan, 1-minute sit to stand dinilai hanya dari jumlah repetisi (cut-off Borg tidak dipakai) dan kata "Borg" hilang dari layar. | Alasan: Keputusan pemilik: lisensi ditolak berarti instrumen diganti atau dimatikan. Menilai sit to stand tanpa Borg mengubah apa yang dihitung sebagai keterbatasan.<br>Sumber: PRD 10 W6 (lisensi instrumen); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-20 | Pengganti ODI/SPADI yang dimatikan: PSFS | Aturan: pada assessment baru atau yang belum selesai, ODI atau SPADI yang dimatikan diganti PSFS untuk area itu. | Alasan: PRD 04 sudah menyebut PSFS sebagai alternatif (dikutip di PRD 10).<br>Sumber: PRD 10 W6 (lisensi instrumen); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-IN-21 | Monitoring tanpa instrumen yang dimatikan | Aturan: monitoring tidak menanyakan instrumen yang dimatikan (tidak ada baseline untuk penggantinya); keputusan memakai nyeri, perubahan menurut pasien dan instrumen lain. Staf melihat "dinonaktifkan (lisensi)" di perbandingan. | Alasan: Keputusan pemilik (opsi a): program tetap berjalan.<br>Sumber: PRD 10 W6 (lisensi instrumen); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

**Pertanyaan terbuka**

- Instrumen mana yang sudah dipakai dan dilisensikan klinik Anda?
- Siapa penerjemah teks instrumen versi Indonesia di aplikasi? Tidak tercatat di kode (lihat prd/legal/instrument-licences.md).

## 5. Kategori risiko dan kegawatdaruratan

Reviewer yang disarankan: Fisioterapis; dokter untuk aturan kegawatdaruratan · 17 parameter · 17 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-RS-01 | Nyeri "Tinggi" mulai dari | 7 (NRS 0–10) | Alasan: NRS 7–10.<br>Sumber: Komentar kode menyebut PRD (NRS 7–10); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-02 | LEFS "Tinggi" bila di bawah | 30 (poin dari 80) | Alasan: LEFS < 30 dari 80.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-03 | ODI "Tinggi" bila di atas | 40 (%) | Alasan: ODI > 40 % (disabilitas berat).<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-04 | SPADI "Tinggi" bila di atas | 50 (%) | Alasan: SPADI > 50 %.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-05 | PSFS rata-rata "Tinggi" bila di bawah | 4 (poin dari 10) | Alasan: Rata-rata PSFS < 4.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-06 | Barthel "Tinggi" bila ≤ | 60 (poin dari 100) | Alasan: Barthel ≤ 60 (ketergantungan berat).<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-07 | Checklist risiko jatuh "Tinggi" bila ≥ | 7 (poin dari 14) | Alasan: Skor ≥ 7.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-08 | TUG "Tinggi" bila ≥ | 20 (detik) | Alasan: TUG ≥ 20 detik.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-09 | mMRC "Tinggi" bila ≥ | 3 (grade 0–4) | Alasan: mMRC ≥ 3.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-10 | Sit to stand "Tinggi" bila di bawah | 12 (repetisi) | Alasan: < 12 repetisi.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-11 | Borg "Tinggi" bila ≥ | 7 (skala 0–10) | Alasan: Borg ≥ 7 ("sangat berat").<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-12 | Nadi istirahat "Tinggi" bila di atas | 120 (denyut/menit) | Alasan: Nadi > 120.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-13 | Napas istirahat "Tinggi" bila di atas | 24 (napas/menit) | Alasan: Napas > 24.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-14 | Selisih lingkar bengkak "Tinggi" bila ≥ | 4 (cm) | Alasan: Selisih ≥ 4 cm.<br>Sumber: Kode menyebut "published cut-offs"; publikasi tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-15 | Temuan yang dianggap kegawatdaruratan (program berhenti seperti red flag) | spo2_critical; wound_infection | Alasan: SpO2 < 90 % dan tanda infeksi luka.<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-16 | Kategori keseluruhan = band tertinggi | Kategori pasien = band tertinggi dari nyeri dan semua instrumen; "Sedang" mulai tepat di cut-off keterbatasan; pediatrik tanpa instrumen = "Sedang" sampai fisioterapis menilai. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-RS-17 | Band per instrumen dan kondisi "Tinggi" khusus | TUG atau sit to stand "tidak dapat dilakukan" = Tinggi; edema berat = Tinggi; infeksi luka = Tinggi dan darurat; SpO2 < 90 % = Tinggi dan darurat. Sit to stand: Borg hanya dipakai bila Borg CR10 aktif. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

## 6. Program latihan, dosis dan pustaka latihan

Reviewer yang disarankan: Fisioterapis · 48 parameter · 48 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-PG-01 | Jumlah level intensitas | 3 (level) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-02 | Jumlah latihan per jenis dalam program | 2 (latihan) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-03 | Interval evaluasi jalur Risiko Rendah | 4 (minggu) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-04 | Interval evaluasi jalur Risiko Sedang | 2 (minggu) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-05 | Interval evaluasi jalur Risiko Tinggi | 1 (minggu) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-06 | Rekomendasi jalur Risiko Rendah | Edukasi kondisi dan prinsip latihan; Program latihan mandiri (teks, video menyusul); Panduan ergonomi dan modifikasi aktivitas; Leaflet / infografis; Monitoring mandiri melalui aplikasi | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-07 | Rekomendasi jalur Risiko Sedang | Konsultasi online dengan fisioterapis; Program latihan terstruktur dengan pemantauan bertahap; Edukasi manajemen nyeri, aktivitas, dan gaya hidup; Video call / telekonsultasi berkala; Monitoring perkembangan (skor nyeri, fungsi, dan aktivitas) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-08 | Rekomendasi jalur Risiko Tinggi | Konsultasi segera dengan fisioterapis (telekonsultasi) atau rujuk ke fasilitas kesehatan; Evaluasi lebih lanjut melalui pemeriksaan klinis / luring; Manajemen awal: posisi aman dan aktivitas yang harus dihindari; Koordinasi dengan dokter atau tenaga kesehatan lain jika diperlukan | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-09 | Materi edukasi jalur Risiko Rendah | Prinsip latihan: Latihan rutin lebih penting daripada latihan berat. Usahakan setiap hari. / Rasa pegal ringan setelah latihan wajar dan hilang dalam 1–2 hari. / Naikkan jumlah atau durasi secara bertahap.<br>Ergonomi & modifikasi aktivitas: Atur layar setinggi mata dan kursi sehingga kaki menapak lantai. / Selingi duduk lama dengan berdiri dan bergerak setiap 30–60 menit. / Saat mengangkat, tekuk lutut dan dekatkan barang ke tubuh. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-10 | Materi edukasi jalur Risiko Sedang | Manajemen nyeri: Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam. / Kompres hangat untuk otot kaku, kompres dingin untuk area bengkak atau terasa panas. / Atur jeda istirahat di antara aktivitas berat.<br>Aktivitas & gaya hidup: Tetap aktif sesuai kemampuan; hindari tirah baring lama. / Tidur cukup 7–9 jam dan jaga berat badan ideal. / Catat latihan Anda agar fisioterapis dapat memantau perkembangan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-11 | Materi edukasi jalur Risiko Tinggi | Posisi aman: Pilih posisi yang paling nyaman dan tidak menambah nyeri; ganti posisi secara berkala. / Gunakan alat bantu jalan bila Anda biasa memakainya, dan pastikan rumah bebas dari benda yang bisa membuat tersandung.<br>Aktivitas yang harus dihindari: Jangan memulai latihan baru atau olahraga berat sebelum diperiksa. / Hindari mengangkat beban berat, gerakan memutar badan mendadak, dan aktivitas yang memicu nyeri hebat atau sesak.<br>Segera ke fasilitas kesehatan bila: Nyeri dada, sesak berat, pingsan, kelemahan mendadak, atau demam tinggi. / Hubungi 112 / 119 atau datang ke IGD bila kondisi memburuk. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-12 | Interval evaluasi Maintenance Program | 4 (minggu) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-13 | Level intensitas Maintenance Program | 2 (level) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-14 | Jenis latihan per kategori risiko | Rendah: peregangan, penguatan, aerobik, relaksasi. Sedang: napas (kardiopulmonal, geriatri, neuromuskular), ROM, penguatan, keseimbangan (geriatri, neuromuskular, atau tungkai bawah). Tinggi: tidak ada. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-15 | Kapan sistem boleh memberi latihan mandiri | Tidak untuk Risiko Tinggi dan tidak untuk domain pediatrik. | Alasan: Risiko Tinggi tidak pernah; pediatrik disusun fisioterapis (komentar kode).<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-16 | Area latihan dari area keluhan | Tungkai bawah, punggung bawah (termasuk bokong), bahu, leher (termasuk punggung atas). | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-17 | Pemilihan latihan dari pustaka | Per jenis: latihan untuk area keluhan dulu, lalu latihan umum; maksimal sejumlah "latihan per jenis". | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-18 | Tabel dosis per kategori dan level | Repetisi: Rendah 3 set × 10 (+2 per level), Sedang 2 set × 8 (+2 per level). Tahan: Rendah 3 × 30 detik, Sedang 2 × 20 detik; keseimbangan 20/15 detik (+5 per level). Menit: Rendah 15, Sedang 10 (+5 per level); relaksasi 10 menit. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-19 | Tabel dosis yang dipakai per jalur | Jalur terbimbing memakai dosis Risiko Sedang; jalur lain memakai dosis Risiko Rendah. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-20 | Peringatan umum saat latihan | Hentikan jika muncul nyeri tajam, pusing, sesak berat, atau nyeri dada. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-21 | Latihan pustaka: Peregangan paha belakang (duduk) | Peregangan paha belakang (duduk) · jenis: stretching · area: lower_limb · satuan dosis: seconds<br>Petunjuk: Duduk di tepi kursi, luruskan satu tungkai ke depan dengan tumit di lantai. / Condongkan badan ke depan dengan punggung tetap lurus sampai terasa tarikan ringan di paha belakang. / Tahan, lalu ganti tungkai. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-22 | Latihan pustaka: Peregangan betis di dinding | Peregangan betis di dinding · jenis: stretching · area: lower_limb · satuan dosis: seconds<br>Petunjuk: Berdiri menghadap dinding, kedua tangan menempel di dinding. / Langkahkan satu kaki ke belakang, tumit tetap menempel lantai, tekuk lutut depan. / Tahan saat terasa tarikan di betis belakang, lalu ganti kaki. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-23 | Latihan pustaka: Lutut ke dada | Lutut ke dada · jenis: stretching · area: low_back · satuan dosis: seconds<br>Petunjuk: Berbaring telentang dengan kedua lutut ditekuk. / Tarik satu lutut perlahan ke arah dada dengan kedua tangan. / Tahan, turunkan perlahan, lalu ganti sisi. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-24 | Latihan pustaka: Peregangan bahu menyilang | Peregangan bahu menyilang · jenis: stretching · area: shoulder · satuan dosis: seconds<br>Petunjuk: Berdiri atau duduk tegak. / Bawa satu lengan menyilang di depan dada, tahan dengan tangan lainnya di atas siku. / Tahan saat terasa tarikan di bahu belakang, lalu ganti sisi. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-25 | Latihan pustaka: Peregangan leher ke samping | Peregangan leher ke samping · jenis: stretching · area: neck · satuan dosis: seconds<br>Petunjuk: Duduk tegak, bahu rileks. / Miringkan kepala perlahan ke arah bahu kanan sampai terasa tarikan ringan di sisi kiri leher. / Tahan, kembali ke tengah, lalu ke sisi lain. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-26 | Latihan pustaka: Meraih ke atas | Meraih ke atas · jenis: stretching · area: general · satuan dosis: seconds<br>Petunjuk: Berdiri tegak, kaki selebar bahu. / Angkat kedua lengan ke atas seolah meraih langit-langit sambil menarik napas. / Tahan, lalu turunkan perlahan sambil membuang napas. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-27 | Latihan pustaka: Angkat tungkai lurus | Angkat tungkai lurus · jenis: strengthening · area: lower_limb · satuan dosis: reps<br>Petunjuk: Berbaring telentang, satu lutut ditekuk dan tungkai lainnya lurus. / Kencangkan otot paha depan, angkat tungkai lurus setinggi lutut yang ditekuk. / Turunkan perlahan. Ulangi, lalu ganti tungkai. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-28 | Latihan pustaka: Duduk–berdiri dari kursi | Duduk–berdiri dari kursi · jenis: strengthening · area: lower_limb · satuan dosis: reps<br>Petunjuk: Duduk di kursi kokoh tanpa roda, kaki selebar bahu. / Condongkan badan sedikit ke depan, berdiri tanpa (atau dengan sedikit) bantuan tangan. / Duduk kembali perlahan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-29 | Latihan pustaka: Jinjit (heel raise) | Jinjit (heel raise) · jenis: strengthening · area: lower_limb · satuan dosis: reps<br>Petunjuk: Berdiri di belakang kursi dan berpegangan. / Angkat kedua tumit setinggi mungkin, tahan sebentar. / Turunkan perlahan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-30 | Latihan pustaka: Bridging (angkat panggul) | Bridging (angkat panggul) · jenis: strengthening · area: low_back · satuan dosis: reps<br>Petunjuk: Berbaring telentang, kedua lutut ditekuk, telapak kaki di lantai. / Kencangkan otot perut dan bokong, angkat panggul sampai badan lurus dari bahu ke lutut. / Tahan 3 detik, turunkan perlahan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-31 | Latihan pustaka: Bird dog | Bird dog · jenis: strengthening · area: low_back · satuan dosis: reps<br>Petunjuk: Posisi merangkak, tangan di bawah bahu dan lutut di bawah panggul. / Luruskan satu lengan ke depan dan tungkai seberangnya ke belakang, punggung tetap datar. / Tahan 3 detik, kembali, lalu ganti sisi. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-32 | Latihan pustaka: Push-up ke dinding | Push-up ke dinding · jenis: strengthening · area: shoulder · satuan dosis: reps<br>Petunjuk: Berdiri menghadap dinding sejauh satu lengan, telapak tangan di dinding setinggi bahu. / Tekuk siku perlahan sampai dada mendekati dinding. / Dorong kembali ke posisi awal. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-33 | Latihan pustaka: Tarik dagu (chin tuck) | Tarik dagu (chin tuck) · jenis: strengthening · area: neck · satuan dosis: reps<br>Petunjuk: Duduk tegak dengan pandangan lurus ke depan. / Tarik dagu lurus ke belakang seperti membuat dagu ganda, tanpa menunduk. / Tahan 5 detik, lalu lepaskan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-34 | Latihan pustaka: Jalan kaki santai | Jalan kaki santai · jenis: aerobic · area: general · satuan dosis: minutes<br>Petunjuk: Berjalan di permukaan rata dengan kecepatan nyaman. / Anda seharusnya masih bisa berbicara sambil berjalan. / Gunakan alas kaki yang nyaman. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-35 | Latihan pustaka: Jalan di tempat | Jalan di tempat · jenis: aerobic · area: general · satuan dosis: minutes<br>Petunjuk: Berdiri di dekat kursi atau dinding untuk berpegangan bila perlu. / Angkat lutut bergantian seperti berjalan di tempat, ayunkan lengan. / Jaga napas tetap teratur. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-36 | Latihan pustaka: Relaksasi otot progresif | Relaksasi otot progresif · jenis: relaxation · area: general · satuan dosis: minutes<br>Petunjuk: Berbaring atau duduk nyaman, pejamkan mata. / Kencangkan satu kelompok otot (mulai dari kaki) selama 5 detik, lalu lepaskan dan rasakan otot mengendur. / Lanjutkan ke betis, paha, perut, tangan, bahu, dan wajah. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-37 | Latihan pustaka: Pompa pergelangan kaki | Pompa pergelangan kaki · jenis: rom · area: lower_limb · satuan dosis: reps<br>Petunjuk: Berbaring atau duduk dengan tungkai lurus. / Tarik ujung kaki ke arah Anda, lalu dorong menjauh. / Lakukan perlahan dan berirama. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-38 | Latihan pustaka: Geser tumit (heel slide) | Geser tumit (heel slide) · jenis: rom · area: lower_limb · satuan dosis: reps<br>Petunjuk: Berbaring telentang dengan tungkai lurus. / Geser tumit mendekati bokong sehingga lutut menekuk sejauh nyaman. / Geser kembali sampai lurus. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-39 | Latihan pustaka: Pelvic tilt | Pelvic tilt · jenis: rom · area: low_back · satuan dosis: reps<br>Petunjuk: Berbaring telentang, kedua lutut ditekuk. / Kencangkan otot perut dan tekan punggung bawah ke lantai. / Tahan 3 detik, lalu lepaskan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-40 | Latihan pustaka: Cat–camel | Cat–camel · jenis: rom · area: low_back · satuan dosis: reps<br>Petunjuk: Posisi merangkak. / Lengkungkan punggung ke atas seperti kucing sambil menunduk, lalu turunkan punggung perlahan sambil mengangkat kepala. / Gerakkan perlahan dalam rentang yang nyaman. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-41 | Latihan pustaka: Pendulum bahu | Pendulum bahu · jenis: rom · area: shoulder · satuan dosis: reps<br>Petunjuk: Berdiri membungkuk dengan satu tangan bertumpu di meja. / Biarkan lengan yang sakit menggantung rileks. / Ayunkan lengan perlahan membentuk lingkaran kecil, searah lalu berlawanan arah jarum jam. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-42 | Latihan pustaka: Jari merambat di dinding | Jari merambat di dinding · jenis: rom · area: shoulder · satuan dosis: reps<br>Petunjuk: Berdiri menghadap dinding. / Rambatkan jari-jari ke atas dinding setinggi yang nyaman. / Turunkan perlahan dengan cara yang sama. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-43 | Latihan pustaka: Putar kepala perlahan | Putar kepala perlahan · jenis: rom · area: neck · satuan dosis: reps<br>Petunjuk: Duduk tegak, bahu rileks. / Putar kepala perlahan menoleh ke kanan sejauh nyaman, kembali ke tengah, lalu ke kiri. / Jangan memaksakan gerakan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-44 | Latihan pustaka: Memindahkan berat badan | Memindahkan berat badan · jenis: balance · area: general · satuan dosis: seconds<br>Petunjuk: Berdiri di belakang kursi kokoh dan berpegangan. / Pindahkan berat badan perlahan ke kaki kanan, tahan, lalu ke kaki kiri. / Jaga badan tetap tegak. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-45 | Latihan pustaka: Berdiri satu garis (tandem) | Berdiri satu garis (tandem) · jenis: balance · area: general · satuan dosis: seconds<br>Petunjuk: Berdiri di samping meja atau kursi dan berpegangan. / Letakkan satu kaki tepat di depan kaki lainnya (tumit menyentuh ujung jari). / Tahan, lalu ganti kaki depan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-46 | Latihan pustaka: Berdiri satu kaki | Berdiri satu kaki · jenis: balance · area: general · satuan dosis: seconds<br>Petunjuk: Berdiri di belakang kursi kokoh dan berpegangan. / Angkat satu kaki sedikit dari lantai. / Tahan, lalu ganti kaki. Lakukan hanya dengan pegangan dan pendamping bila perlu. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-47 | Latihan pustaka: Pernapasan diafragma | Pernapasan diafragma · jenis: breathing · area: general · satuan dosis: reps<br>Petunjuk: Duduk atau berbaring nyaman, satu tangan di dada dan satu di perut. / Tarik napas perlahan lewat hidung sampai perut terangkat, dada tetap tenang. / Buang napas perlahan lewat mulut. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-PG-48 | Latihan pustaka: Pursed-lip breathing | Pursed-lip breathing · jenis: breathing · area: general · satuan dosis: reps<br>Petunjuk: Duduk rileks. / Tarik napas lewat hidung selama 2 hitungan. / Buang napas lewat bibir yang dimonyongkan (seperti meniup lilin) selama 4 hitungan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: Pustaka bawaan ditulis untuk aplikasi (DRAFT); sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

**Pertanyaan terbuka**

- Pustaka latihan bawaan (28 latihan) ditulis untuk aplikasi tanpa sumber klinis tercatat. Setiap latihan perlu disetujui atau diganti.
- Interval evaluasi jalur Risiko Tinggi (1 minggu) ada di kode, tetapi pasien Risiko Tinggi tidak mendapat program. Dipakai untuk apa, atau dihapus?

## 7. Monitoring dan reassessment

Reviewer yang disarankan: Fisioterapis · 26 parameter · 26 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-MO-01 | Minggu tanpa perbaikan sebelum reassessment | 4 (minggu) | Alasan: Komentar kode mengutip PRD: "tidak terjadi perbaikan setelah 4–6 minggu".<br>Sumber: Sumber klinis tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-02 | Kenaikan nyeri yang dianggap memburuk drastis | 3 (poin NRS) | Alasan: NRS naik 3 atau lebih (komentar kode).<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-03 | Nyeri maksimal untuk "tujuan tercapai" | 2 (NRS 0–10) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-04 | Kepatuhan rendah (cek kepatuhan dulu sebelum mengubah dosis) | 50 (% hari) | Alasan: Di bawah nilai ini, kepatuhan ditinjau dulu sebelum dosis diubah (komentar kode).<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-05 | MCID dan arah perbaikan: NRS | 2 (lebih rendah lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-06 | MCID dan arah perbaikan: LEFS | 9 (lebih tinggi lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-07 | MCID dan arah perbaikan: ODI | 10 (lebih rendah lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-08 | MCID dan arah perbaikan: SPADI | 13 (lebih rendah lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-09 | MCID dan arah perbaikan: PSFS | 2 (lebih tinggi lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-10 | MCID dan arah perbaikan: BARTHEL | 10 (lebih tinggi lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-11 | MCID dan arah perbaikan: STEADI | 2 (lebih rendah lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-12 | MCID dan arah perbaikan: TUG | 2 (lebih rendah lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-13 | MCID dan arah perbaikan: MMRC | 1 (lebih rendah lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-14 | MCID dan arah perbaikan: STS | 3 (lebih tinggi lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-15 | MCID dan arah perbaikan: EDEMA | 1 (lebih rendah lebih baik) | Alasan: Nilai = [perubahan minimal yang bermakna, arah yang lebih baik].<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-16 | Perbandingan dengan baseline | Nyeri dan setiap instrumen yang punya MCID dibandingkan dengan assessment awal. Instrumen yang dimatikan (lisensi) ditampilkan sebagai "dinonaktifkan" dan tidak dibandingkan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-17 | Arah dan kebermaknaan perubahan | Bermakna bila perubahan ≥ MCID ke arah yang ditentukan; "tidak dapat dilakukan" dihitung sebagai nilai terburuk. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-18 | Aturan keputusan monitoring | Drastis (darurat, Risiko Tinggi, atau nyeri naik ≥ batas) → rujukan. Tujuan tercapai (Risiko Rendah, nyeri ≤ batas, tidak memburuk) → Maintenance. Membaik bermakna → progresif. Memburuk, keluhan baru, efek samping, atau tanpa perbaikan ≥ batas minggu → reassessment. Selain itu → modifikasi. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-19 | Penyesuaian program setelah monitoring | Progresif: level +1 dan satu variasi latihan. Modifikasi: level tetap bila kepatuhan rendah, selain itu level −1. Reassessment/rujukan: program dijeda; darurat menjadi red flag. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-20 | Variasi latihan yang lebih menantang | Satu latihan dari jenis yang sama yang belum ada di program. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-21 | Saran untuk hambatan "pain" | Kurangi jumlah atau jangkauan gerak sampai nyeri saat latihan ringan (≤ 3/10), dan konsultasikan bila tetap nyeri. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-22 | Saran untuk hambatan "motivation" | Tetapkan jam latihan yang sama setiap hari dan ajak anggota keluarga berlatih bersama. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-23 | Saran untuk hambatan "time" | Bagi latihan menjadi 2 sesi pendek, misalnya pagi dan sore. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-24 | Saran untuk hambatan "environment" | Gunakan kursi kokoh, dinding, atau handuk sebagai pengganti alat; pilih area yang aman dari tersandung. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-25 | Saran untuk hambatan "technique" | Ajukan telekonsultasi agar fisioterapis meninjau teknik latihan Anda. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-MO-26 | Saran untuk hambatan "forgot" | Pasang alarm harian dan buka halaman Today setiap pagi. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

**Pertanyaan terbuka**

- MCID per instrumen: dari publikasi mana? Belum tercatat.

## 8. Umum: IMT, kelompok usia, kelompok khusus

Reviewer yang disarankan: Fisioterapis · 6 parameter · 6 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-GN-01 | Kategori IMT (BMI) | < 18,5 kurus; 18,5–24,9 normal; 25–29,9 berat badan lebih; ≥ 30 obesitas. (kg/m²) | Alasan: tidak diketahui, perlu diisi<br>Sumber: Batas 18,5 / 25 / 30; klasifikasi yang dipakai (WHO atau Kemenkes) tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-GN-02 | Kelompok usia (pediatrik, dewasa, geriatri) | ≤ 17 pediatrik; 18–59 dewasa; ≥ 60 geriatri. (tahun) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-GN-03 | Kelompok khusus pada screening | Usia < 18 → pediatrik; kehamilan (hanya perempuan) → kehamilan; usia ≥ 60 → geriatri; riwayat operasi/cedera atau keluhan pasca cedera → pasca operasi/cedera; selain itu → "lainnya". Kehamilan belum punya screening atau domain sendiri. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-GN-04 | Pertanyaan tambahan per keluhan | Nyeri → tingkat nyeri, penjalaran, gangguan tidur, karakter nyeri; kekakuan → pertanyaan kekakuan; kelemahan → kesemutan. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-GN-05 | Keluhan yang wajib memilih area tubuh | pain; weakness; mobility; stiffness; post_injury | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-GN-06 | Label tingkat nyeri NRS | Label kata untuk setiap angka nyeri 0–10 (lihat kode). (NRS 0–10) | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

**Pertanyaan terbuka**

- Klasifikasi IMT mana yang dipakai: WHO (18,5 / 25 / 30) seperti sekarang, atau klasifikasi Kementerian Kesehatan untuk dewasa Indonesia?
- Apakah kelompok usia (di bawah 18, 60 ke atas) sudah tepat?
- Kehamilan belum punya screening atau domain sendiri: pasien hamil diproses seperti pasien lain. Apa yang harus dilakukan aplikasi?

## 9. Teks keselamatan untuk pasien

Reviewer yang disarankan: Dokter dan fisioterapis · 7 parameter · 7 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-TX-01 | Halaman berhenti red flag: apa yang perlu dilakukan (112 / 119) | Teks untuk pasien (lihat blok di kode). | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-TX-02 | Halaman berhenti red flag: "bukan diagnosis" | Teks untuk pasien (lihat blok di kode). | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-TX-03 | Peringatan Risiko Tinggi pada hasil klasifikasi (112 / 119) | Teks untuk pasien (lihat blok di kode). | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-TX-04 | Catatan dan disclaimer Clinical Assessment | Teks untuk pasien (lihat blok di kode). | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-TX-05 | Catatan dan disclaimer rekomendasi intervensi | Teks untuk pasien (lihat blok di kode). | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-TX-06 | Pesan monitoring: reassessment dan rujukan (112 / 119) | Teks untuk pasien (lihat blok di kode). | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-TX-07 | Halaman depan: "bukan diagnosis" | Teks untuk pasien (lihat blok di kode). | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

**Pertanyaan terbuka**

- Apakah kalimat "bukan diagnosis" dan saran ke fasilitas kesehatan sudah cukup jelas dan benar?

## 10. Sisi fisioterapis (PRD 09)

Reviewer yang disarankan: Fisioterapis · 6 parameter · 6 tanpa sumber tercatat.

| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |
| --- | --- | --- | --- | --- | --- |
| P-ST-01 | Hari tanpa log latihan sebelum "tidak ada aktivitas" | 5 (hari) | Alasan: DRAFT dari PRD 09 (keputusan D5).<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-ST-02 | Jendela kepatuhan untuk "perlu perhatian" | 7 (hari) | Alasan: Nilai PRD Monitoring (7 hari), disebut di PRD 09.<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-ST-03 | Alasan "perlu perhatian" | Konsultasi terbuka, red flag aktif, program perlu ditinjau, kondisi memburuk (evaluasi "lebih buruk", reassessment atau rujukan), evaluasi terlambat, kepatuhan 7 hari < 50 %, tanpa aktivitas ≥ batas hari. | Alasan: tidak diketahui, perlu diisi<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-ST-04 | Batas atas dosis yang boleh diatur fisioterapis | sets: 5<br>reps: 20<br>hold_seconds: 60<br>minutes: 45 | Alasan: DRAFT dari PRD 09 S3.3.<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-ST-05 | Interval evaluasi program yang dibuat fisioterapis | 2 (minggu) | Alasan: DRAFT dari PRD 09 S3.2.<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |
| P-ST-06 | Kapan fisioterapis tidak boleh membuat program | Tidak untuk pasien yang dihentikan red flag, belum punya klasifikasi risiko, Risiko Tinggi, atau sudah punya program berjalan. | Alasan: Keputusan D3 PRD 09.<br>Sumber: tidak diketahui, perlu diisi | [ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus |  |

## 11. Pertanyaan untuk pemilik dan pengacara (bukan untuk reviewer klinis)

- Apakah aplikasi ini termasuk alat kesehatan (medical device) atau hanya perangkat lunak edukasi pasien menurut aturan Indonesia?
- Apa yang diwajibkan aturan telemedisin dan pelindungan data pribadi untuk data kesehatan dan untuk staf yang bekerja lintas klinik?
- Kalimat persetujuan (consent) dan pemberitahuan privasi apa yang diperlukan?
- Apa yang harus dinyatakan aplikasi tentang sifatnya yang tidak mendiagnosis?
