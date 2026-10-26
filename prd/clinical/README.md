# Review klinis: cara mengisi `decisions.csv`

File di folder ini (PRD 10):

| File | Isi |
| --- | --- |
| `parameters.json` | Register semua nilai, aturan dan teks klinis (sumber kebenaran). Jangan diubah manual. |
| `review-sheet.md` | Lembar review untuk reviewer, dengan blok tanda tangan. |
| `vignettes.md` | Contoh kasus yang dihitung oleh aplikasi. |
| `decisions.csv` | Keputusan reviewer, satu baris per parameter. **Masih kosong.** |

## Mengisi `decisions.csv` (W3)

Isi **hanya** dari lembar review yang sudah ditandatangani reviewer. Satu baris per ID; baris yang dibiarkan kosong tetap `draft`.

| Kolom | Isi |
| --- | --- |
| `id` | ID parameter, jangan diubah (mis. `P-RF-17`) |
| `decision` | `Setuju`, `Ubah` atau `Hapus` |
| `new_value` | Hanya untuk `Ubah`: nilai atau teks baru persis seperti ditulis reviewer |
| `reviewer` | Nama, profesi dan nomor STR/SIP reviewer, sesuai yang ia setujui untuk dicantumkan (mis. `Nama Lengkap, Fisioterapis, STR 1234567890`) |
| `date` | Tanggal review, format `YYYY-MM-DD` |
| `comment` | Alasan atau sumber dari reviewer (boleh kosong) |

Aturan:

- Pemilik aplikasi tidak menandatangani keputusan klinis atas nama reviewer. Kolom `reviewer` hanya berisi orang yang benar-benar mereview.
- Red flag dan aturan kegawatdaruratan perlu reviewer dokter; parameter lain cukup fisioterapis (lihat saran per modul di `review-sheet.md`).
- Lembar review yang ditandatangani disimpan **di luar** repositori; cukup sebut nama filenya di kolom `comment` baris pertama yang Anda isi.
- Setelah selesai, serahkan file ini ke Claude Code untuk W4: setiap keputusan diterapkan satu commit, lalu register, dokumen PRD, tes dan contoh kasus diperbarui.
