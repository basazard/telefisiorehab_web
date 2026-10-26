# Register Lisensi Instrumen TeleFisioRehab

> PRD 10 W6 langkah 1. Dokumen ini **bukan nasihat hukum**. Semua "titik awal" di bawah berasal dari PRD 10 dan harus diverifikasi langsung ke pemegang hak.
> Sel yang belum diketahui ditulis "tidak diketahui". Pemilik memperbarui kolom status, kontak, tanggal, biaya, atribusi dan masa berlaku setelah menghubungi pemegang hak.
> **Switch (W6 langkah 3):** ODI, SPADI dan Borg CR10 bisa dimatikan di `.env` server: `CLINICAL_ODI_ENABLED=false`, `CLINICAL_SPADI_ENABLED=false`,
> `CLINICAL_BORG_ENABLED=false` (lalu `php artisan config:cache`). ODI/SPADI mati: area itu memakai PSFS. Borg mati: 1-minute sit to stand dinilai dari
> jumlah repetisi saja dan kata "Borg" hilang dari layar (pertanyaan pengganti hanya dengan teks dan cut-off yang disetujui reviewer).
> Monitoring program yang baseline-nya memakai instrumen yang dimatikan melewati instrumen itu. Status di bawah tetap ditentukan pemilik.

## Penggunaan oleh aplikasi (berlaku untuk semua instrumen pasien di bawah)

Deskripsi jujur untuk disampaikan ke pemegang hak: aplikasi web komersial untuk fisioterapi di rumah di Indonesia. Pasien mengisi kuesioner atau tes di dalam aplikasi,
jawaban disimpan (`assessments.answers`, `program_evaluations.answers`), skor dihitung otomatis dan ditampilkan kepada pasien (hasil assessment, monitoring)
serta kepada staf klinik (detail pasien, perbandingan progres). Teks pertanyaan dan pilihan jawaban versi Indonesia tersimpan di kode aplikasi.

**Penerjemah teks Indonesia: tidak diketahui untuk semua instrumen.** Kode tidak mencatat siapa yang menerjemahkan atau dari versi mana.

## Ringkasan

| Instrumen | Dipakai untuk | Status | Pemegang hak (perlu verifikasi) |
| --- | --- | --- | --- |
| ODI | Punggung bawah (pasien mengisi) | not started | Mapi Research Trust (titik awal) |
| SPADI | Bahu (pasien mengisi) | not started | tidak diketahui |
| Borg CR10 | Sesak setelah 1-minute sit to stand (pasien mengisi) | not started | Borg (borgperception.se, titik awal) |
| LEFS | Tungkai bawah (pasien mengisi) | not started | tidak diketahui |
| PSFS | Area lain dan olahraga (pasien mengisi) | not started | tidak diketahui |
| Barthel Index | Neuromuskular dan geriatri (pasien atau pendamping mengisi) | not started | tidak diketahui |
| mMRC | Kardiopulmonal (pasien mengisi) | not started | tidak diketahui |
| CDC STEADI "Stay Independent" | Risiko jatuh geriatri (pasien mengisi) | not started | tidak diketahui |
| TUG | Neuromuskular dan geriatri (tes mandiri dengan pendamping) | not started | tidak diketahui |
| 1-minute sit to stand | Kardiopulmonal (tes mandiri dengan pendamping) | not started | tidak diketahui |
| MMSE, Denver II / KPSP, GMFM, FMS, Berg Balance Scale, MMT, MAS, 6MWT | Hanya disebut sebagai tes oleh fisioterapis; belum diisi di aplikasi | not started | tidak diketahui |

Status yang dipakai: `not started`, `requested`, `granted`, `refused`, `replaced`.

---

## ODI (Oswestry Disability Index)

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (`ODI_ITEMS`, `ODI_CHOICES`, `ODI_OPTIONAL`, `ODI_LIMIT`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`ODI_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['odi']`), `app/Support/Onboarding/ClinicalAssessment.php` (dipilih untuk punggung bawah), `resources/views/components/instrument-fields.blade.php`. Juga di `app/Support/Onboarding/Questionnaires.php` (`ODI_ITEMS`, `ODI_CHOICES`, kode lama yang tidak dipakai lagi, tetapi teksnya masih ada di repositori) |
| Dipakai di (layar) | Clinical Assessment pasien, hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | 10 item, skor 0–5 per item, dalam bahasa Indonesia; versi asli yang diterjemahkan tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan item, menghitung skor %, menyimpan jawaban, menampilkan hasil ke pasien dan staf |
| Pemegang hak | titik awal: Mapi Research Trust (Lyon, Prancis) mendistribusikan lisensi versi 2.1a; perlu verifikasi |
| Ketentuan penggunaan komersial | titik awal: halaman Mapi menyebut pengguna klinis dan akademik bisa mendapat lisensi tanpa biaya, pengguna komersial diminta membayar biaya lisensi; terjemahan harus dari set yang berlisensi. Perlu verifikasi |
| Status | not started |
| Kontak | titik awal: PROinformation@mapi-trust.org (tercantum pada kuesioner berlisensi); perlu verifikasi |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | Teks terjemahan di aplikasi kemungkinan bukan dari set berlisensi; bila demikian perlu diganti dengan terjemahan resmi |

## SPADI (Shoulder Pain and Disability Index)

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (`SPADI_PAIN`, `SPADI_DISABILITY`, `SPADI_LIMIT`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`SPADI_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['spadi']`), `app/Support/Onboarding/ClinicalAssessment.php` (dipilih untuk bahu), `resources/views/components/instrument-fields.blade.php` |
| Dipakai di (layar) | Clinical Assessment pasien, hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | 5 item nyeri dan 8 item disabilitas, skala 0–10, dalam bahasa Indonesia; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan item, menghitung skor %, menyimpan jawaban, menampilkan hasil ke pasien dan staf |
| Pemegang hak | tidak diketahui; titik awal: cari halaman izin dari penulis asli |
| Ketentuan penggunaan komersial | tidak diketahui; tanyakan apakah aplikasi komersial tercakup |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | |

## Borg CR10

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (`BORG`, `BORG_LIMIT`, judul tes "1-minute sit to stand + Borg Scale", skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`BORG_HIGH`), `resources/views/components/instrument-fields.blade.php` |
| Dipakai di (layar) | Clinical Assessment kardiopulmonal (setelah 1-minute sit to stand), hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | skala 0–10 dengan label kata dalam bahasa Indonesia; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan skala dan label, menyimpan jawaban, memakai nilai untuk keterbatasan dan kategori risiko, menampilkan hasil |
| Pemegang hak | titik awal: situs penulis skala, borgperception.se; perlu verifikasi |
| Ketentuan penggunaan komersial | tidak diketahui. Titik awal: penulis pernah keberatan secara terbuka terhadap perubahan teks skala tanpa izin, jadi versi parafrase berisiko |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | Label kata di aplikasi kemungkinan parafrase. Pengganti tanpa nama "Borg" hanya boleh dengan teks dan cut-off yang disetujui reviewer (PRD 10 W6) |

## LEFS (Lower Extremity Functional Scale)

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (`LEFS_ITEMS`, `LEFS_CHOICES`, `LEFS_LIMIT`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`LEFS_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['lefs']`), `app/Support/Onboarding/ClinicalAssessment.php` (dipilih untuk tungkai bawah), `resources/views/components/instrument-fields.blade.php`. Juga di `app/Support/Onboarding/Questionnaires.php` (`LEFS_ITEMS`, `LEFS_CHOICES`, kode lama yang tidak dipakai lagi) |
| Dipakai di (layar) | Clinical Assessment pasien, hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | 20 item, skor 0–4, maksimal 80, dalam bahasa Indonesia; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan item, menghitung skor, menyimpan jawaban, menampilkan hasil ke pasien dan staf |
| Pemegang hak | tidak diketahui |
| Ketentuan penggunaan komersial | tidak diketahui |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | |

## PSFS (Patient-Specific Functional Scale)

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (`PSFS_SLOTS`, `PSFS_LIMIT`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`PSFS_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['psfs']`), `app/Support/Onboarding/ClinicalAssessment.php` (area lain dan olahraga), `resources/views/components/instrument-fields.blade.php` |
| Dipakai di (layar) | Clinical Assessment pasien, hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | pasien menulis 1–3 aktivitas sendiri dan menilai 0–10; petunjuk dalam bahasa Indonesia; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan petunjuk, menghitung rata-rata, menyimpan jawaban, menampilkan hasil ke pasien dan staf |
| Pemegang hak | tidak diketahui |
| Ketentuan penggunaan komersial | tidak diketahui |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | PRD 10 menyebut PSFS sebagai pengganti bila ODI atau SPADI dimatikan, jadi status PSFS ikut menentukan rencana cadangan |

## Barthel Index

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (`BARTHEL`, `BARTHEL_LIMIT`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`BARTHEL_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['barthel']`), `app/Support/Onboarding/ClinicalAssessment.php` (neuromuskular, geriatri), `resources/views/components/instrument-fields.blade.php` |
| Dipakai di (layar) | Clinical Assessment pasien, hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | 10 item, versi 0–100, dalam bahasa Indonesia; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan item, menghitung skor, menyimpan jawaban, menampilkan hasil ke pasien dan staf |
| Pemegang hak | tidak diketahui |
| Ketentuan penggunaan komersial | tidak diketahui |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | |

## mMRC (modified Medical Research Council Dyspnoea Scale)

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (`MMRC`, `MMRC_LIMIT`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`MMRC_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['mmrc']`), `app/Support/Onboarding/ClinicalAssessment.php` (kardiopulmonal), `resources/views/components/instrument-fields.blade.php` |
| Dipakai di (layar) | Clinical Assessment pasien, hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | grade 0–4, dalam bahasa Indonesia; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan pilihan, menyimpan jawaban, memakai grade untuk keterbatasan dan kategori risiko, menampilkan hasil |
| Pemegang hak | tidak diketahui |
| Ketentuan penggunaan komersial | tidak diketahui |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | |

## CDC STEADI "Stay Independent" (checklist risiko jatuh)

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (`STEADI` dengan bobot per item, `STEADI_LIMIT`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`STEADI_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['steadi']`), `app/Support/Onboarding/ClinicalAssessment.php` (geriatri), `resources/views/components/instrument-fields.blade.php` |
| Dipakai di (layar) | Clinical Assessment pasien (judul di aplikasi: "Penilaian risiko jatuh"), hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | 12 pernyataan ya/tidak dengan bobot, maksimal 14, dalam bahasa Indonesia; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan pernyataan, menghitung skor, menyimpan jawaban, menampilkan hasil ke pasien dan staf |
| Pemegang hak | tidak diketahui (komentar kode menyebut CDC STEADI) |
| Ketentuan penggunaan komersial | tidak diketahui |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | |

## TUG (Timed Up and Go)

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (petunjuk di `INTROS['tug']`, `TUG_LIMIT`, `SUPERVISED`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`TUG_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['tug']`), `app/Support/Onboarding/ClinicalAssessment.php` (neuromuskular, geriatri), `resources/views/components/instrument-fields.blade.php` |
| Dipakai di (layar) | Clinical Assessment pasien (tes mandiri dengan pendamping), hasil assessment, klasifikasi risiko, monitoring; detail pasien untuk staf |
| Versi dan bahasa di aplikasi | petunjuk tes dalam bahasa Indonesia, hasil dalam detik; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan petunjuk tes, menyimpan waktu, memakai waktu untuk keterbatasan dan kategori risiko, menampilkan hasil |
| Pemegang hak | tidak diketahui |
| Ketentuan penggunaan komersial | tidak diketahui |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | |

## 1-minute sit to stand

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Support/Onboarding/Instruments.php` (petunjuk di `INTROS['sts']`, `STS_LIMIT`, `SUPERVISED`, skor di `score()`), `app/Support/Onboarding/RiskStratification.php` (`STS_HIGH`), `app/Support/Onboarding/Monitoring.php` (`MCID['sts']`), `resources/views/components/instrument-fields.blade.php` |
| Dipakai di (layar) | Clinical Assessment kardiopulmonal (tes mandiri dengan pendamping), hasil assessment, klasifikasi risiko, monitoring |
| Versi dan bahasa di aplikasi | petunjuk tes dalam bahasa Indonesia, hasil dalam jumlah repetisi; versi asli tidak diketahui |
| Penerjemah teks Indonesia | tidak diketahui |
| Yang dilakukan aplikasi | menampilkan petunjuk tes, menyimpan jumlah repetisi, memakai nilai untuk keterbatasan dan kategori risiko, menampilkan hasil |
| Pemegang hak | tidak diketahui |
| Ketentuan penggunaan komersial | tidak diketahui |
| Status | not started |
| Kontak | tidak diketahui |
| Tanggal diminta / dijawab | tidak diketahui |
| Biaya | tidak diketahui |
| Teks atribusi yang diwajibkan | tidak diketahui |
| Masa berlaku | tidak diketahui |
| Catatan | Tidak ada di daftar PRD 10; ditambahkan karena dipakai di aplikasi bersama Borg CR10 |

## Tes oleh fisioterapis yang baru disebut (belum diisi di aplikasi)

MMSE, Denver II / KPSP, GMFM, FMS, Berg Balance Scale (BBS), MMT, MAS, 6MWT.

| Kolom | Isi |
| --- | --- |
| Dipakai di (file) | `app/Enums/PhysioDomain.php` (daftar tes per domain), `app/Support/Onboarding/ClinicalAssessment.php` (komentar: Denver II / KPSP, GMFM untuk pediatrik) |
| Dipakai di (layar) | halaman domain, assessment, hasil assessment dan klasifikasi risiko pasien hanya menyebut nama tes yang dilakukan fisioterapis (`PhysioDomain::clinicianInstruments()`) |
| Versi dan bahasa di aplikasi | hanya nama tes; tidak ada item, skor, atau jawaban yang disimpan |
| Penerjemah teks Indonesia | tidak relevan saat ini |
| Yang dilakukan aplikasi | menyebut nama tes saja |
| Pemegang hak | tidak diketahui untuk setiap tes |
| Ketentuan penggunaan komersial | tidak diketahui untuk setiap tes |
| Status | not started |
| Kontak / tanggal / biaya / atribusi / masa berlaku | tidak diketahui |
| Catatan | Harus diperiksa sebelum sisi fisioterapis mulai mengisi tes ini di aplikasi (PRD 10 W6) |

---

## Catatan untuk pemilik

1. Teks item ODI dan LEFS juga masih ada di `app/Support/Onboarding/Questionnaires.php` (kode lama yang tidak dipakai lagi). Bila lisensi ditolak, teks itu ikut harus dihapus dari repositori.
2. Instrumen tidak boleh dipakai pasien sungguhan tanpa status `granted`, atau keputusan tertulis pemilik yang dicatat di dokumen ini (PRD 10 §8).
