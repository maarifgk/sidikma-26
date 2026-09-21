# Laporan perbaikan Dashboard SK dan Presensi

Tanggal: 7 September 2026.

## Audit dan akar masalah

Dashboard SK sebelumnya menuju `/admin/sk-yayasan#dashboard-sk`, yaitu bagian atas halaman pengelolaan file SK `FoundationDecrees`. Ringkasannya menghitung dokumen yang diunggah, bukan pengajuan SK. Sistem pengajuan sudah tersedia pada `DecreeSubmission`/`decree_submissions`, dengan relasi `employee`, `school`, `type`, policy, serta resource daftar/detail sendiri.

Pengaturan presensi memakai `AttendanceSettings`, tabel `attendance_settings`, dan Leaflet. Kolom `office_latitude`, `office_longitude`, `radius_meters`, serta `geofence_polygon` sudah tersedia, masuk atribut Fillable, dan memiliki cast. Tidak diperlukan tabel atau migration baru.

Uji browser berhasil mereproduksi keadaan koordinat sudah tersimpan di database tetapi marker hilang setelah reload. Nilai awal yang diambil melalui `$wire` ketika editor Alpine dinamis sedang diinisialisasi belum menjadi angka; diagnostik mendapatkan fungsi placeholder pada latitude, sedangkan snapshot Livewire dan database berisi koordinat benar. Inisialisasi Leaflet juga perlu dijalankan setelah referensi elemen selesai dipasang. Selain itu, `mount()` selalu memilih sekolah pertama dan `saveSettings()` menimpa radius dengan batas akurasi GPS.

## Perubahan

- Menambahkan halaman Filament `DecreeDashboard` pada `/admin/sk-yayasan/dashboard`. Sidebar Dashboard SK menuju halaman ini; halaman upload lama tetap tersedia.
- Ringkasan menghitung total semua status, dalam peninjauan, diproses, dan selesai dari pengajuan existing. Daftar menampilkan sepuluh pengajuan terbaru dengan relasi eager loading, fallback relasi/tanggal kosong, serta tautan daftar dan detail resource existing.
- Dashboard menggunakan role admin induk dan policy `viewAny` existing. Tidak mengubah sistem autentikasi atau role.
- Data awal peta dirender sebagai JSON oleh Blade dari properti PHP yang dimuat dari database. Inisialisasi JavaScript tidak lagi membaca koordinat melalui proxy `$wire` yang belum siap. Leaflet diinisialisasi setelah `Alpine.initTree()` dan `Alpine.nextTick()`, dengan pemeriksaan agar peta tidak dibuat dua kali.
- Klik peta/geser marker memperbarui koordinat pada state Livewire. Perubahan polygon juga disinkronkan. Form memakai `wire:submit="saveSettings"`; backend memvalidasi lalu menjalankan `updateOrCreate` berdasarkan sekolah yang berhak diakses.
- Sekolah terpilih disimpan di parameter URL `school`, diperiksa hak aksesnya, dan dipulihkan saat reload. Editor memiliki key per sekolah.
- Radius presensi menjadi input tersendiri, tidak ditimpa batas akurasi GPS. Koordinat harus berpasangan dan wajib jika GPS diwajibkan.
- Dua input Latitude/Longitude tidak dikembalikan, sesuai permintaan pengguna sebelumnya. Koordinat tetap terlihat sebagai teks dan dapat disalin. Tombol Lokasi Saya dan Simpan Pengaturan memakai alur existing.
- `MyAttendance` tetap menggunakan koordinat, polygon, dan radius dari `AttendanceSetting` untuk geofence; tidak ada perubahan rumus atau koordinat hardcoded baru.

## File implementasi

1. `app/Filament/Admin/Pages/DecreeDashboard.php` — halaman ringkasan baru.
2. `resources/views/filament/admin/pages/decree-dashboard.blade.php` — ringkasan, daftar, dan empty state.
3. `app/Providers/Filament/AdminPanelProvider.php` — registrasi halaman dan tautan sidebar.
4. `app/Filament/Admin/Pages/AttendanceSettings.php` — sekolah di URL, radius terpisah, validasi koordinat.
5. `resources/views/filament/admin/pages/attendance-settings.blade.php` — data awal JSON, inisialisasi peta, sinkronisasi form, input radius.

File pengujian: `tests/Feature/DecreeDashboardTest.php`, `tests/Feature/AttendanceIntegrationTest.php`, `tests/js/attendance-location.cjs`, serta `tests/browser/{prepare.php,inspect.php,smoke.cjs}`.

## Verifikasi

- Hasil akhir: **29 tes backend lulus, 151 assertions; satu tes JavaScript lulus; seluruh alur Edge headless lulus**, termasuk akun admin sekolah pada `/app/presensi/pengaturan` yang memindahkan, menyimpan, dan membuka ulang marker.
- Suite terkait: `DecreeDashboardTest`, `AttendanceIntegrationTest`, `FoundationDecreePageTest`, `DecreeSubmissionFeatureTest`, dan `AttendanceLocationServiceTest`.
- Memeriksa halaman/dashboard kosong, hitungan seluruh status, relasi null, daftar/detail, penolakan role tanpa hak akses, dan akses sekolah melalui URL.
- Memeriksa penyimpanan koordinat/radius, pembukaan komponen baru, pemisahan antar sekolah, dan presensi guru menggunakan lokasi yang baru disimpan.
- Tes JavaScript memeriksa klik lokasi tanpa membuat polygon tidak lengkap, sinkronisasi polygon, dan pusat peta dari koordinat tersimpan.
- Tes Edge headless menggunakan database SQLite baru per run di `storage/framework/testing/browser-smoke-*`. Login melalui form aplikasi, klik peta, ubah radius, submit, cek notifikasi, reload, simpan polygon, logout/login, dan baca database langsung. Koordinat `-7.9767346, 110.6157939`, radius `350`, dan tiga titik polygon terbukti bertahan. Dashboard SK kosong juga terbuka tanpa exception JavaScript.

## Batasan dan operasional

Tidak ada data aplikasi yang dihapus atau diubah untuk pengujian. Migration hanya dijalankan untuk membangun database uji SQLite baru. Penyimpanan pengaturan tetap satu operasi update record existing; tidak menambahkan rangkaian update database yang memerlukan transaksi baru.

Uji browser menggunakan lingkungan lokal terisolasi, bukan akun atau database produksi. Izin GPS perangkat dan layanan tile OpenStreetMap bergantung pada browser/jaringan pengguna. Peta dan geofence tetap menggunakan pola aplikasi existing.

## Lanjutan: presensi guru/pegawai dan akurasi GPS

Pengambilan lokasi sebelumnya memakai satu hasil `getCurrentPosition`, langsung mengirim tiga request state, kemudian meminta presensi. Hasil awal yang kasar langsung ditolak server. Kini kedua tampilan presensi memakai `public/js/attendance-gps.js`: `watchPosition` menunggu pembacaan yang memenuhi batas sekolah selama maksimal 25 detik, menampilkan akurasi aktual, membersihkan watcher, dan mengirim ketiga nilai GPS dalam satu request aksi presensi. Jika akurasi tidak membaik, aksi tidak dikirim dan petunjuk mencoba lagi ditampilkan. Batas sekolah dan pemeriksaan geofence tetap diterapkan server; akurasi wajib tersedia jika GPS diwajibkan.

File lanjutan: `app/Filament/App/Pages/MyAttendance.php`, kedua view `filament/app/{mobile/attendance,pages/my-attendance}.blade.php`, `public/js/attendance-gps.js`, `tests/js/attendance-gps.cjs`, serta pengembangan tes integrasi dan browser existing.

Tes JavaScript mencakup perbaikan akurasi dan timeout tanpa submit. Uji Edge dengan lokasi simulasi membuktikan akun guru menunggu ketika akurasi 800 meter, kemudian berhasil presensi saat akurasi menjadi 10 meter. Ini memverifikasi alur aplikasi; kemampuan sensor GPS perangkat pengguna tidak bisa diperbaiki oleh aplikasi.
