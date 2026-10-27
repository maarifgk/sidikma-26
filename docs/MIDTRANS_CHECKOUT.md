# Pembayaran online melalui Midtrans Snap

Alur `/admin/pembayaran`: cari pegawai → **Bayar** → modal tagihan → pilih **Online — Midtrans** → **Lanjutkan ke Midtrans**. Snap menampilkan kanal yang tersedia pada akun merchant. Satu checkout membayar satu invoice; nominal dibaca dari database, bukan browser. Metode manual tetap menggunakan pencatatan oleh admin yang berhak.

## Penerapan hosting

Setelah kode ini diterapkan, jalankan migrasi melalui prosedur deployment proyek:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Migrasi menambahkan mode Midtrans (default Sandbox), Snap token terenkripsi, dan relasi histori transaksi online. `legacy_payment_id` menjadi nullable agar histori online tidak memerlukan ID sumber palsu. Tidak ada invoice lama yang diubah oleh migrasi. Backup database mengikuti prosedur hosting sebelum migrasi produksi.

Di **Pengaturan → Aplikasi**, simpan server key, client key, dan mode yang sesuai. Key Sandbox memiliki awalan `SB-`. Jangan mencampur key Sandbox dan Production. Jangan mengirim server key melalui chat atau menaruhnya di JavaScript.

Di dashboard Midtrans untuk mode yang sama, atur **Payment Notification URL**:

```text
https://app.sidikma.id/payments/midtrans/notification
```

URL harus dapat menerima POST JSON dari internet tanpa login. Hanya endpoint ini yang dikecualikan dari CSRF. Signature diverifikasi dan status diperiksa kembali melalui API Midtrans sebelum mengubah invoice. Firewall hosting harus mengizinkan notifikasi tersebut dan HTTPS keluar ke Midtrans.

Uji Sandbox menggunakan invoice khusus pengujian: pembayaran Sandbox yang terverifikasi juga mengubah invoice dan menambahkan histori di database aplikasi yang digunakan. Setelah pengujian, gunakan pasangan key Production dan pilih mode Production untuk pembayaran sungguhan. Kanal pembayaran yang muncul ditentukan akun merchant Midtrans.

## Perilaku dan batasan

- Checkout browser hanya memperoleh client key dan Snap token; server key dipakai oleh backend. Form pengaturan key tetap hanya dapat diakses admin yang berhak.
- Invoice yang masih berstatus belum lunas tetapi memiliki histori pembayaran sukses ditahan untuk rekonsiliasi sisa tagihan; aplikasi tidak otomatis menagih ulang nominal penuh.
- Order pending digunakan kembali untuk mencegah klik berulang membuat order berbeda. Invoice lunas tidak dapat dibuatkan checkout baru.
- Status dari callback JavaScript tidak dianggap bukti pembayaran. Notifikasi atau tombol **Periksa Status Pembayaran** memeriksa API server Midtrans.
- Histori sukses disimpan sekali per transaksi dan invoice ditandai lunas secara atomik. Histori legacy tidak diubah. Database lama `tagihan` tidak disinkron balik.
- Akses invoice mengikuti policy yang sudah ada. Invoice migrasi dapat dibayar jika relasinya valid dan pengguna berhak. Pengecualian invoice karantina ID 837 dari alur lama tetap dipertahankan.
- Jika pembuatan checkout timeout, order dipertahankan karena Midtrans mungkin sudah menerima permintaan. Bila percobaan ulang tetap gagal, admin perlu memeriksa order yang ditampilkan di dashboard Midtrans sebelum pembatalan/rekonsiliasi; aplikasi tidak otomatis membuat order pengganti yang berisiko pembayaran ganda.
- Refund dicatat sebagai status transaksi Midtrans untuk rekonsiliasi admin; tidak otomatis membuka ulang invoice atau membalik histori pembayaran sukses. Pengembalian dana bukan bagian dari tombol Bayar ini.
- Rollback migrasi diblokir jika sudah ada histori online yang tidak memiliki ID legacy, agar histori tersebut tidak rusak.

## Validasi lokal

Gunakan SQLite pengujian, bukan database hosting. Test HTTP Midtrans di-fake dan tidak melakukan transaksi nyata:

```bash
DB_DATABASE=:memory: SIDIKMA_DB_DATABASE=:memory: vendor/bin/phpunit tests/Feature/MidtransCheckoutTest.php tests/Feature/PaymentPageTest.php tests/Feature/ApplicationSettingsPageTest.php tests/Feature/LocalPaymentPersistenceTest.php tests/Feature/SecurityHardeningTest.php --process-isolation
```

Process isolation diperlukan untuk menghindari pemakaian ulang koneksi SQLite in-memory antar-test pada test harness proyek ini.

Referensi: [Integrasi Snap](https://docs.midtrans.com/docs/snap-snap-integration-guide), [HTTP notifications](https://docs.midtrans.com/docs/https-notification-webhooks).
