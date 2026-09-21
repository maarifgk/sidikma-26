# Hasil Migrasi SIDIKMA

Tanggal eksekusi: 13 Agustus 2026 (Asia/Jakarta)

Audit delta terakhir: 21 Agustus 2026 (Asia/Jakarta)

## Status

Migrasi data terstruktur dari database SIDIKMA (MySQL) ke aplikasi baru (PostgreSQL) telah dijalankan secara transaksional dan idempoten. Database sumber hanya dibaca.

## Ringkasan hasil

| Modul | Data legacy termigrasi |
|---|---:|
| Pengguna | 689 |
| Sekolah | 62 (60 baru, 2 sudah ada) |
| Pegawai | 626 |
| Invoice | 714 |
| Histori pembayaran | 625 |
| Kas | 17 |
| Usulan SK | 66 |
| Mutasi | 19 |
| Pengaturan absensi | 58 |
| Baris absensi | 469, digabung menjadi rekap harian |
| Izin absensi | 4 |
| Persuratan | 24 |
| Proposal bantuan | 16 |
| Rekap siswa | 210 sumber, dikonsolidasikan menjadi 163 kombinasi sekolah/tahun |
| Rekap pendidik | 158 sumber, dikonsolidasikan menjadi 116 kombinasi sekolah/tahun |
| Update SIPINTER | 60 |
| Aktivasi/nonaktif pegawai | 18 (11 dipertahankan sebagai snapshot tanpa relasi pegawai) |
| Produk batik | 5 (3 target diperbarui, 2 dibuat) |
| Pesanan batik | 83 |
| Agenda sekretariat | 1 |
| Modul pembelajaran | 72 |

Format tahun ajaran rekap siswa dan pendidik telah dinormalisasi dari bentuk legacy `YYYY/YY` menjadi format aplikasi `YYYY/YYYY` tanpa mengubah struktur aplikasi. Rekap siswa yang dapat ditampilkan adalah 45 sekolah/3.376 siswa (2023/2024), 45/3.327 (2024/2025), 62/4.132 (2025/2026), dan 11/613 (2026/2027). Rekap pendidik juga dinormalisasi pada periode yang sama. Satu bentrok sekolah-tahun digabung secara transaksional dengan data sebelum normalisasi disimpan dalam snapshot audit.

Satu baris kas legacy bernilai numerik nol dilewati. Nilai pada teks tidak ditebak agar saldo tidak berubah berdasarkan asumsi. Lima usulan SK dan sebelas aktivasi yang tidak dapat dipasangkan secara pasti tetap disimpan sebagai snapshot, tanpa membuat relasi palsu.

## Pemeriksaan keselamatan

- Dry-run setelah migrasi menunjukkan `Baru = 0` pada seluruh modul tersisa.
- Seluruh migration sampai `2026_08_13_165000` berstatus `Ran`.
- Audit foreign key pada absensi, izin, persuratan, proposal, serta pesanan batik menemukan 0 orphan.
- Pengujian khusus migrasi dan pesanan batik lulus. Suite penuh 21 Agustus menyelesaikan 335 test: 316 lulus dengan 2.212 assertion dan 19 gagal pada assertion teks/tombol UI lama; kegagalan dapat direproduksi terpisah dan tidak menyentuh integritas data migrasi.

## Berkas fisik

Arsip `storage (1).zip` telah lulus pengujian integritas (1.729 file, 26 folder). Sebanyak 1.717 file biasa disimpan sebagai arsip privat di `storage/app/private/sidikma-legacy` dan dicatat dalam manifest SHA-256. Audit ulang 21 Agustus memastikan 198 referensi database sudah terpasang pada disk privat modul. Tidak ada lagi file cocok unik yang menunggu dipasang. Ada 305 referensi yang berkasnya tidak ada di arsip dan 5 nama ambigu; daftar audit tersimpan di `storage/app/private/sidikma-files-missing.txt` dan `storage/app/private/sidikma-files-ambiguous.txt`. Arsip tidak memuat folder selfie `attendance` maupun berkas `modul`, sehingga path tersebut belum dapat dipasang.

Pada audit delta 21 Agustus, dua pegawai aktif (target ID 266 dan 587) ditemukan dalam keadaan soft-delete. Keduanya dipulihkan setelah ID user, nama, kode pegawai, dan status sumber cocok persis. Setelah pemulihan, 626 dari 626 pegawai dan seluruh 469 baris absensi terpetakan. Sebanyak 570 foto profil legacy valid dipasang ulang; bersama empat foto lokal, seluruh 574 path avatar aktif mempunyai file fisik. Sebanyak 350 record dokumen SK pegawai tersedia secara privat dan seluruh 350 path telah diverifikasi mempunyai file fisik. Tabel legacy `sk_yayasan_documents` memiliki 262 metadata dokumen, tetapi 261 file fisiknya tidak terdapat dalam ZIP dan satu record tidak memiliki user target; semuanya dicatat tanpa menebak file.

Sebanyak 295 record yang tidak mempunyai struktur operasional setara di aplikasi baru disimpan pada `legacy_migration_records`: profil lembaga (1), template SK HTML-builder (1), konfigurasi nomor SK (5), metadata dokumen SK (262), SK sekolah (1), broadcast (24), dan konfigurasi aplikasi non-rahasia (1). Token WhatsApp, server key, dan client key sengaja tidak disalin. Template HTML hanya disimpan sebagai JSON dan tidak pernah dirender. Alamat serta telepon yayasan yang kosong diisi dari profil lembaga lama tanpa menimpa konfigurasi aplikasi lokal versi 1.5.0.

## Perintah verifikasi ulang

```powershell
php artisan sidikma:migrate-users --dry-run
php artisan sidikma:migrate-master-data --dry-run
php artisan sidikma:migrate-schools --dry-run
php artisan sidikma:migrate-employees --dry-run
php artisan sidikma:migrate-finance --dry-run
php artisan sidikma:migrate-workflows --dry-run
php artisan sidikma:migrate-remaining --dry-run
php artisan sidikma:migrate-legacy-metadata --dry-run
```
