# Panduan Pemasangan SIDIKMA 26 V9

Paket `sidikma26-usulan-sk-edit-status-v9.zip` hanya berisi source code modul Usulan SK Baru. Pemasangan belum dilakukan dan path aplikasi produksi harus dikonfirmasi terlebih dahulu.

## 1. Identifikasi direktori aplikasi

```bash
pwd
find . -maxdepth 2 -type f -name artisan -print
```

Tetapkan path hasil pemeriksaan sebagai `APP_DIR`. Jangan menebak path produksi.

## 2. Backup source yang akan ditimpa

```bash
cd APP_DIR
BACKUP_DIR="$HOME/backups/sidikma26-v9-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/source-before-v9.tar.gz" \
  app/Filament/Resources/DecreeProposals \
  app/Models/DecreeProposal.php \
  app/Policies/DecreeProposalPolicy.php \
  app/Services/DecreeProposal* \
  resources/views/filament/shared/decree-proposal-detail-styles.blade.php
```

Verifikasi arsip backup sebelum melanjutkan:

```bash
tar -tzf "$BACKUP_DIR/source-before-v9.tar.gz" | less
```

## 3. Upload dan pemeriksaan paket

Upload ZIP melalui SCP/SFTP ke direktori sementara, bukan langsung ke direktori aplikasi:

```bash
scp sidikma26-usulan-sk-edit-status-v9.zip USER@HOST:~/sidikma26-v9-upload/
ssh USER@HOST
cd ~/sidikma26-v9-upload
unzip -l sidikma26-usulan-sk-edit-status-v9.zip
sha256sum sidikma26-usulan-sk-edit-status-v9.zip
```

Pastikan daftar tersebut hanya berisi file source yang disetujui dan tidak berisi `.env`, database, `tests/`, `vendor/`, `node_modules/`, atau `storage/`.

## 4. Ekstraksi dan pemasangan

```bash
mkdir -p ~/sidikma26-v9-extracted
unzip -q sidikma26-usulan-sk-edit-status-v9.zip -d ~/sidikma26-v9-extracted
diff -ruN ~/sidikma26-v9-extracted APP_DIR
```

Setelah hasil `diff` diperiksa, salin hanya isi paket ke `APP_DIR` dengan mempertahankan struktur direktori. Jangan menghapus file lain.

## 5. Pemeriksaan setelah pemasangan

```bash
cd APP_DIR
for f in $(find app resources -type f -name '*.php'); do php -l "$f" || exit 1; done
php artisan optimize:clear
php artisan about
```

`optimize:clear` hanya dijalankan setelah pemasangan disetujui dan setelah backup berhasil. Jangan menjalankan migration, seeder, reset, truncate, atau VACUUM.

Periksa halaman Usulan SK Baru secara manual dengan akun uji yang berwenang. Jangan menggunakan data pribadi sebagai sampel.

## 6. Rollback

Hentikan perubahan aplikasi sesuai prosedur hosting, lalu pulihkan source dari backup:

```bash
cd APP_DIR
tar -xzf "$BACKUP_DIR/source-before-v9.tar.gz" -C APP_DIR
php artisan optimize:clear
```

File baru yang tidak ada pada backup harus diidentifikasi dari daftar paket dan dipindahkan secara manual ke direktori karantina setelah verifikasi, bukan dihapus massal. Rollback source code tidak membatalkan transaksi database yang sudah terjadi; perubahan data harus ditangani melalui prosedur database terpisah yang telah disetujui.

Jangan menjalankan pemasangan sebelum path aplikasi, metode backup, versi PHP, dan kompatibilitas MySQL dikonfirmasi.
