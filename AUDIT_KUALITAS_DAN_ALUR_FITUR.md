# Laporan Audit Kualitas, Alur Fitur, Menu, Database, dan Pembayaran

Tanggal audit: 5 November 2026  
Proyek: `C:\laragon\www\yayasan-app`  
Metode: inspeksi kode, route, konfigurasi, migration, policy, dan pemeriksaan CLI read-only.

## Ringkasan eksekutif

Masalah utama yang terbukti bukan berupa error terpisah pada setiap menu. Aplikasi gagal mencapai database utama saat audit:

```text
SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it
Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: yayasan_mysql
```

Dampaknya bersifat domino: halaman Filament, autentikasi berbasis database, session/queue/cache berbasis database, presensi, pembayaran, laporan, dan resource yang membaca model akan gagal atau tampil kosong ketika koneksi ini tidak tersedia.

Status keseluruhan: **belum layak diuji sebagai aplikasi fungsional penuh** sampai koneksi MySQL dipulihkan dan status migration diverifikasi.

## Cakupan arsitektur

Inventaris statis menemukan:

- 38 resource Filament.
- 21 page Admin/App.
- 61 model.
- 130 migration, termasuk 22 migration bernama `repair`.
- 103 file test.
- Dua panel utama: `/admin` dan `/app`.
- Route non-Filament untuk dokumen, attachment, presensi, peta, dan pembayaran.

Menu/modul yang terlihat dari route dan resource mencakup dashboard, users, sekolah, profil lembaga, pegawai, posisi/mutasi/aktivitas pegawai, tahun ajaran, siswa, rekap pendidik, presensi dan izin, pembayaran/tagihan/tunggakan/jenis pembayaran/laporan keuangan, proposal, surat-menyurat, SK yayasan, pengaduan, agenda sekretariat, modul pembelajaran, batik, treasury, audit log, dan pengaturan aplikasi.

## Temuan prioritas

### TINGGI-01 — Database utama tidak dapat dihubungi

Lokasi: `.env` (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`), `config/database.php:20,47-65`.

Bukti:

- Konfigurasi aktif memakai `mysql`, `127.0.0.1:3306`, database `yayasan_mysql`.
- `php artisan migrate:status` gagal dengan koneksi ditolak.
- `php artisan db:show --counts` gagal dengan error yang sama.

Dampak: hampir seluruh menu yang membaca/menulis database dapat error 500, redirect login gagal, tabel tampak kosong, session database/queue database tidak berjalan, dan status pembayaran tidak bisa dibuat atau direkonsiliasi.

Perbaikan paling sederhana: nyalakan service MySQL yang benar atau koreksi `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` sesuai server aplikasi; setelah itu jalankan pemeriksaan koneksi dan migration status read-only sebelum menguji menu.

### TINGGI-02 — Konfigurasi koneksi `pgsql` tidak benar-benar menetapkan driver PostgreSQL

Lokasi: `config/database.php:87-100`.

Bukti: entry bernama `pgsql` mengisi `driver` dengan `env('SIDIKMA_DB_CONNECTION', env('DB_CONNECTION', 'mysql'))`. Dengan `.env` saat ini `SIDIKMA_DB_CONNECTION` kosong dan `DB_CONNECTION=mysql`, maka koneksi bernama `pgsql` sebenarnya memakai driver MySQL tetapi memakai default port/charset PostgreSQL.

Dampak: command/test/migrasi yang memilih koneksi `pgsql` dapat menghasilkan error driver, port, parameter, atau schema yang membingungkan. Ini sangat berisiko untuk laporan, migrasi, dan fitur Foundation/SIDIKMA.

Perbaikan paling sederhana: tetapkan driver `pgsql` secara literal `pgsql`; gunakan koneksi terpisah untuk SIDIKMA dengan nama dan env yang konsisten.

### TINGGI-03 — Tidak ada verifikasi runtime database sebelum menyalahkan fitur/menu

Lokasi: `routes/web.php`, seluruh resource Filament; `routes/console.php` memiliki `app:production-check`.

Bukti: `app:production-check` memang memeriksa `Schema::hasTable('users')`, tetapi command tersebut sendiri akan gagal saat database tidak terhubung. Tidak terlihat health-check yang memberi pesan terarah pada halaman aplikasi sebelum query resource berjalan.

Dampak: pengguna melihat error berbeda-beda di banyak menu, padahal akar masalah sama. Troubleshooting menjadi lambat dan berisiko dilakukan dengan perubahan migration/DB yang tidak tepat.

Perbaikan paling sederhana: pastikan health-check dijalankan sebagai pemeriksaan deployment dan tambahkan monitoring/logging koneksi; jangan menjalankan migration repair berulang sebagai solusi koneksi.

### TINGGI-04 — Kredensial Midtrans tidak tersedia di `.env` dan alur pembayaran bergantung pada setting database

Lokasi: `.env`, `app/Services/Payments/MidtransGateway.php:10-45`, `app/Filament/Admin/Pages/ApplicationSettings.php`, `routes/web.php:24`.

Bukti:

- `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` tidak ada di `.env`.
- Gateway tidak mengambil key dari `.env`, tetapi mengambil record `ApplicationSetting` dari database.
- Checkout membuat transaksi lokal terlebih dahulu, lalu memanggil Snap Midtrans.
- Notification memvalidasi signature lalu melakukan query status server-to-server.

Dampak: pembayaran online tidak akan bisa dimulai jika tabel/settings tidak tersedia atau key belum diisi. Jika koneksi database mati, checkout, pengaturan key, dan sinkronisasi webhook seluruhnya gagal.

Perbaikan paling sederhana: pulihkan database, isi pasangan key sandbox/production yang benar melalui pengaturan aplikasi, cocokkan mode, pasang URL notification publik HTTPS, lalu uji transaksi sandbox.

### TINGGI-05 — Callback lokal pembayaran memakai endpoint POST yang hanya dilindungi login/permission

Lokasi: `routes/web.php:30-33`, `app/Http/Controllers/LocalPaymentController.php`, `app/Http/Controllers/SafeLocalPaymentController.php`.

Bukti: endpoint `/payments/local/callback` menggunakan `auth` group dan pengecekan `foundation.update`; tidak terlihat signature webhook eksternal. Layanan `SafeLocalPaymentService` membatasi `source=local_mock`, status, nominal, invoice, dan transaksi terminal.

Dampak: endpoint bukan webhook publik Midtrans dan hanya cocok untuk simulasi/admin. Salah konfigurasi frontend atau integrasi eksternal dapat menghasilkan 401/403 atau status tidak berubah. Jangan menganggap endpoint ini sebagai callback pembayaran produksi.

Perbaikan paling sederhana: pisahkan endpoint simulasi dari webhook produksi; webhook Midtrans tetap memakai signature dan status server-to-server seperti alur `MidtransNotificationController`.

### SEDANG-01 — Hardcode ID invoice 837 di alur pembayaran

Lokasi: `app/Services/Payments/MidtransPaymentService.php:26`, `app/Http/Controllers/LocalPaymentController.php:4`.

Bukti: checkout dan halaman pembayaran secara eksplisit menolak invoice dengan ID `837`.

Dampak: perilaku bisnis tidak transparan dan dapat memblokir invoice valid setelah data berubah/migrasi. Ini indikasi workaround historis yang dapat menyebabkan fitur tampak “acak” bagi operator.

Perbaikan paling sederhana: ganti dengan aturan bisnis bernama/kolom status/flag yang terdokumentasi dan migrasikan data terkait secara eksplisit.

### SEDANG-02 — Migration repair terlalu banyak dan belum dapat diverifikasi statusnya

Lokasi: `database/migrations/`, khususnya 22 file bernama `repair` pada rentang 24-27 Oktober 2026.

Bukti: terdapat repair untuk invoice, fee, academic years, payment types, attendance events, foundation SK, users, dan banyak tabel lain. Status migration tidak dapat dibaca karena database menolak koneksi.

Dampak: jika sebagian migration sudah berjalan dan sebagian belum, struktur tabel bisa berbeda dari asumsi model/resource. Gejala yang muncul akan terlihat sebagai “menu error database”, misalnya kolom/tabel/foreign key tidak ada.

Perbaikan paling sederhana: setelah DB hidup, simpan output `migrate:status`, cocokkan schema aktual dengan migration, lalu audit migration yang gagal sebelum menjalankan apa pun di production.

### SEDANG-03 — Session, queue, dan cache harus selaras dengan kesiapan database

Lokasi: `.env`, `config/session.php`, `config/queue.php`, `config/cache.php`.

Bukti: `QUEUE_CONNECTION=database`, sedangkan `SESSION_DRIVER=file` dan `CACHE_STORE=file` pada environment lokal. Environment production example mengarahkan queue/cache/session ke database.

Dampak: di production, database yang bermasalah dapat membuat login/session, queue pekerjaan, cache, dan notifikasi ikut gagal; di lokal perilaku berbeda sehingga bug sulit direproduksi.

Perbaikan paling sederhana: tetapkan pilihan driver per environment secara sengaja, pastikan tabel `jobs`, `failed_jobs`, `sessions`, dan `cache` tersedia jika memakai database, serta dokumentasikan worker queue.

### SEDANG-04 — Audit browser end-to-end belum dapat dilakukan karena aplikasi tidak punya koneksi database aktif

Lokasi: seluruh panel `/admin` dan `/app`.

Bukti: `route:list` berhasil membangun route, tetapi pemeriksaan `migrate:status`/`db:show` gagal sebelum query aplikasi dapat diuji.

Dampak: laporan ini dapat memastikan masalah infrastruktur dan cacat statis, tetapi belum dapat menyatakan setiap menu berhasil/gagal berdasarkan klik dan data nyata.

Perbaikan paling sederhana: ulangi smoke test per role setelah DB hidup: login, dashboard, CRUD inti, upload/download, approval, presensi, laporan, dan pembayaran sandbox.

### RENDAH-01 — Working tree memiliki file tidak terlacak bernama `tatus --short`

Lokasi: root repository.

Bukti: `git status --short` menghasilkan `?? "tatus --short"`.

Dampak: dapat mengotori deployment/commit dan mengindikasikan command redirect/operasi sebelumnya salah. Tidak memengaruhi runtime secara langsung.

Perbaikan paling sederhana: identifikasi isi dan asal file, lalu hapus atau simpan secara sadar pada tahap housekeeping terpisah. Tidak dilakukan dalam audit ini.

## Alur pembayaran yang ditemukan

1. Pengguna membuka pembayaran dari panel Filament.
2. `MidtransPaymentService::checkout()` mengecek policy invoice, status unpaid, nominal, histori paid, dan transaksi aktif.
3. Sistem membuat reservasi `midtrans_transactions` dalam transaksi database.
4. Gateway memanggil Snap API Midtrans dan menyimpan token terenkripsi.
5. Browser memuat `snap.js` dan membuka Snap.
6. Midtrans mengirim notification ke `POST /payments/midtrans/notification`.
7. Aplikasi memvalidasi signature, mengambil status server-to-server, mencocokkan order/currency/gross amount, lalu mengubah invoice menjadi paid dan membuat `payment_transactions`.

Secara desain, pemisahan “browser callback tidak dipercaya” dan “status server-to-server dipercaya” sudah tepat. Titik gagalnya saat ini adalah database/settings/key/akses jaringan, bukan bukti bahwa Snap API sudah berhasil terhubung.

## Risiko keamanan dan hak akses

- Route download dokumen/attachment memakai `auth`; sebagian controller juga melakukan policy check. Ini perlu diuji dengan dua akun berbeda setelah DB aktif.
- Policy dan visibility Filament tersebar di resource, page, provider, dan policy. Risiko regresi tinggi karena menu bisa tersembunyi tetapi URL masih harus diuji langsung.
- `APP_DEBUG=false` sudah baik pada `.env` lokal; namun `SESSION_ENCRYPT=false` dan `SESSION_SECURE_COOKIE` tidak diatur. Untuk HTTPS production, secure cookie harus dipastikan aktif.
- File dokumen memakai beberapa disk private/public. Download controller harus diuji terhadap ID record milik sekolah lain untuk mencegah IDOR.

## Duplikasi/AI slop dan maintainability

- Banyak class/service ditulis sangat padat satu baris, terutama beberapa controller/service/resource. Ini menyulitkan review, debugging, dan deteksi authorization bug.
- Terdapat dua jalur pembayaran lokal (`LocalPaymentService` dan `SafeLocalPaymentService`) serta interface gateway yang binding-nya adalah `LocalPaymentGateway`; perlu dokumentasi yang membedakan simulasi dan produksi.
- Ada resource yang sengaja `shouldRegisterNavigation=false` tetapi tetap memiliki route. Ini bisa benar sebagai subfitur, namun harus dipastikan setiap link parent memakai route yang benar.
- Rangkaian migration repair yang besar meningkatkan risiko schema drift. Repair migration perlu diperlakukan sebagai sejarah perubahan, bukan mekanisme rutin memperbaiki semua error runtime.

## Rencana verifikasi setelah koneksi diperbaiki

1. Jalankan `php artisan about`, `php artisan migrate:status`, dan `php artisan db:show --counts`.
2. Pastikan semua migration expected berstatus Ran dan tidak ada migration gagal.
3. Jalankan smoke test terpisah untuk Admin Induk, Admin Sekolah/Madrasah, Guru/Pegawai, dan user biasa.
4. Uji menu dashboard, master data, CRUD, approval, file, presensi, laporan, dan pencarian global.
5. Uji pembayaran sandbox: buat invoice, checkout, notification valid, notification duplikat, nominal salah, signature salah, expired, dan refund.
6. Periksa `storage/logs/laravel.log`, failed jobs, queue worker, serta response HTTP 500/403/404.
7. Baru setelah itu lakukan perbaikan kode; jangan menjalankan `migrate:fresh`, rollback massal, atau menghapus data production.

## Batasan audit

Audit ini tidak mengubah file aplikasi, konfigurasi, database, atau data. Tidak ada commit/push. Karena MySQL aktif menolak koneksi, data produksi, schema aktual, isi tabel, log runtime, dan pengujian klik end-to-end belum dapat divalidasi. Temuan yang diberi status “terbukti” adalah yang didukung konfigurasi/kode atau error CLI nyata; temuan menu individual harus dikonfirmasi pada audit tahap kedua setelah database tersedia.
