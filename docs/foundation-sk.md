# Foundation SK

Modul ini mengelola template, nomor, upload/import, generate PDF, dan dokumen SK Yayasan.

## Menu dan hak akses

Menu berada di grup **SK Yayasan**: Dashboard, Dokumen SK, Template SK, dan Pengaturan Nomor SK.

- `sk-yayasan.view`: melihat dashboard, template, dan dokumen sesuai scope.
- `sk-yayasan.manage`: upload, import, generate, dan mengelola template.
- Admin-induk memiliki scope seluruh sekolah.
- Admin-sekolah-madrasah dibatasi `accessibleSchoolIds()`.
- Guru/pegawai hanya dapat mengakses dokumen miliknya melalui policy.

## Instalasi

```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\FoundationSkSeeder
```

Seeder bersifat idempotent. Disk `documents` menggunakan private storage (`storage/app/private/documents`). Dokumen tidak boleh dibuka melalui URL publik; gunakan controller download terotorisasi.

## Operasional

Template dikelola melalui Template SK. Pengaturan nomor dibuat per periode. Upload satu file dan import batch tersedia pada Dashboard/Dokumen SK. Generate PDF dapat diunduh langsung atau disimpan sebagai dokumen generated; batch dapat diunduh sebagai ZIP.

## Troubleshooting

Jika muncul `relation foundation_sk_documents does not exist`, jalankan `php artisan migrate` pada database aplikasi, lalu `php artisan optimize:clear`. Pastikan disk `documents` tersedia dan ekstensi ZIP aktif untuk export ZIP.

## Concurrency

Reservasi nomor memakai transaction dan `lockForUpdate()`. Verifikasi concurrency harus menggunakan MySQL/PostgreSQL; SQLite hanya cocok untuk test fungsional dan tidak membuktikan row locking.
