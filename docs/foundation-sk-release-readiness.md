# Foundation SK — Release Readiness

Tanggal verifikasi: 17 September 2026

## Status akhir

**Feature-complete dan lulus test khusus, tetapi belum tervalidasi penuh untuk concurrency dan end-to-end browser.**

## Lulus

- Migration Foundation SK sudah diterapkan:
  - `foundation_sk_templates`
  - `foundation_sk_number_settings`
  - `foundation_sk_documents`
- `FoundationSkSeeder` idempotent dan sudah dijalankan dua kali tanpa membuat duplikasi.
- Unit test: 30 test lulus, 54 assertion.
- `FoundationSkBatchGenerationTest`: 2 test lulus, 12 assertion.
- `FoundationSkBatchFailureTest`: 2 test lulus, 16 assertion.
- Seluruh test pada `tests/Feature/FoundationSk`: 4 test lulus, 28 assertion.
- Route Foundation SK tersedia untuk dashboard, dokumen, template, nomor, preview, generate, batch, dan download.
- Dokumen menggunakan disk private `documents`.
- Download dilakukan melalui controller dan policy terotorisasi.
- Tidak ditemukan penggunaan `Storage::url()` pada Foundation SK.
- Tidak ditemukan test Foundation SK yang hang; seluruh file Foundation SK selesai sekitar 1–2 detik per file.
- Permission tersedia: `sk-yayasan.view` dan `sk-yayasan.manage`.
- Modul Decree tidak diubah.

## Pending karena environment

- Concurrency PostgreSQL/MySQL belum dijalankan dengan dua worker/proses nyata. Test concurrency di SQLite berstatus skipped karena SQLite tidak membuktikan row locking.
- Browser/session role test belum dijalankan dengan session nyata.
- IDOR end-to-end dengan dua akun dan HTTP response 403/404 belum diverifikasi melalui browser/session nyata.
- Full Feature regression aplikasi belum lulus secara keseluruhan.

## Failure unrelated

Failure berikut bukan berasal dari Foundation SK dan tidak boleh diatribusikan kepada modul tersebut:

- `AdministrationRequirementEditorsTest` — assertion editor modul administrasi.
- `AttendanceIntegrationTest` — polygon lokasi sekolah belum dikonfigurasi.
- `CorrespondenceRequestResourceTest` — field jenis surat wajib belum terisi.
- Failure lain pada modul non-Foundation SK yang ditemukan saat suite Feature dijalankan.

## Manual acceptance checklist

### Admin-induk

- [ ] Membuka Dashboard SK Yayasan.
- [ ] Membuka Dokumen SK, Template SK, dan Pengaturan Nomor SK.
- [ ] Membuat dan mengedit template.
- [ ] Mengatur nomor SK.
- [ ] Upload dokumen satu user.
- [ ] Mengganti file dan memastikan file lama dibersihkan setelah sukses.
- [ ] Menjalankan generate PDF satu user.
- [ ] Menjalankan generate batch ZIP.
- [ ] Download dokumen melalui route private.
- [ ] Melihat data seluruh sekolah.

### Admin-sekolah-madrasah

- [ ] Melihat dokumen sekolah yang diizinkan.
- [ ] Tidak dapat melihat atau memproses sekolah lain.
- [ ] Upload/generate hanya untuk user sekolah yang diizinkan.
- [ ] Percobaan mengubah parameter sekolah lain ditolak.

### Guru/pegawai

- [ ] Tidak melihat menu resource admin Foundation SK.
- [ ] Tidak dapat membuka resource admin.
- [ ] Hanya dapat melihat atau download dokumen miliknya sendiri.
- [ ] Tidak dapat generate PDF atau import batch.

### IDOR download

- [ ] Login sebagai user A.
- [ ] Siapkan dokumen milik user B.
- [ ] Akses `/foundation-sk/documents/{id-user-B}/download`.
- [ ] Pastikan response 403 atau 404.
- [ ] Pastikan path, nama file, dan isi dokumen tidak bocor.

### Generate dan upload

- [ ] Generate PDF satu user berhasil.
- [ ] Nomor SK unik dan counter bertambah sesuai hasil sukses.
- [ ] Batch ZIP berisi satu PDF per user.
- [ ] ZIP temporary terhapus setelah response.
- [ ] Upload PDF/JPG/PNG valid berhasil.
- [ ] File invalid ditolak.
- [ ] Update dokumen tidak membuat record duplikat.
- [ ] Saat proses gagal, file lama tetap ada dan file baru dibersihkan.

## Menjalankan concurrency test

SQLite hanya digunakan untuk test fungsional. Untuk memvalidasi `lockForUpdate()`, gunakan PostgreSQL atau MySQL dengan database test khusus.

Contoh PostgreSQL PowerShell:

```powershell
$env:DB_CONNECTION = 'pgsql'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '5432'
$env:DB_DATABASE = 'yayasan_concurrency_test'
$env:DB_USERNAME = 'postgres'
$env:DB_PASSWORD = 'password-test'
php artisan migrate:fresh --force
php artisan test tests/Integration/FoundationSk/FoundationSkBatchConcurrencyTest.php --process-isolation
```

Jalankan dua worker batch secara bersamaan pada periode yang sama. Verifikasi nomor tidak duplikat, counter akhir sama dengan jumlah dokumen sukses, tidak ada duplicate document, dan tidak ada deadlock.

Contoh MySQL:

```powershell
$env:DB_CONNECTION = 'mysql'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3306'
$env:DB_DATABASE = 'yayasan_concurrency_test'
$env:DB_USERNAME = 'root'
$env:DB_PASSWORD = ''
php artisan migrate:fresh --force
php artisan test tests/Integration/FoundationSk/FoundationSkBatchConcurrencyTest.php --process-isolation
```

Jangan menjalankan concurrency test terhadap database production.

## Release decision

Foundation SK layak dianggap feature-complete untuk test khusus dan deployment terkontrol. Rilis penuh tetap menunggu bukti concurrency PostgreSQL/MySQL serta acceptance test browser/session dan IDOR end-to-end.
