# Checklist Keamanan Produksi

Sebelum deployment, atur pada `.env` server (jangan commit nilainya):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-aplikasi
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Kemudian jalankan:

```powershell
php artisan app:production-check
php artisan migrate --force
php artisan optimize
```

Checklist operasional:

- Ganti seluruh password akun development dan password database.
- Gunakan sertifikat HTTPS yang valid.
- Simpan `APP_KEY`, kredensial database, dan token hanya di secret manager atau `.env` server.
- Audit kembali permission tiap role.
- Atur batas request dan upload pada web server sesuai kapasitas server.
- Siapkan backup PostgreSQL dan `storage/app`, lalu uji proses restore.
- Jalankan `composer audit --locked --no-dev` pada server yang memiliki akses internet.
- Pastikan worker queue dan scheduler berjalan serta dimonitor.

Login Filament sudah membatasi maksimal lima percobaan pada setiap jendela rate limit dan meregenerasi session setelah login berhasil.
