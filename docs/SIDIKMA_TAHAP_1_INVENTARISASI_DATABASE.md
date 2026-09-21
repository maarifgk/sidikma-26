# Tahap 1 — Inventarisasi dan Mapping Database SIDIKMA

Tanggal inventarisasi: 13 Agustus 2026  
Sumber: MariaDB koneksi `mysql_sidikma`, schema domain `ABC` (read-only)  
Target: PostgreSQL koneksi `pgsql`, schema `public` (read-only pada tahap ini)

## Ringkasan

- Schema `ABC` memiliki **58 tabel** dengan sekitar **16.403 baris**. Tabel terbesar adalah `mm_logs` (12.505), lalu `tagihan` (714), `users` (692), `payment` (625), dan `attendances` (469).
- PostgreSQL memiliki **63 tabel** dan 694 user setelah migrasi user yang telah selesai.
- MariaDB `ABC` tidak mendeklarasikan foreign key sama sekali. Relasi hanya tersirat dari nama kolom dan pemakaian aplikasi (`user_id`, `kelas_id`, `madrasah_id`, dan lain-lain).
- PostgreSQL memakai foreign key dan unique constraint secara luas. Karena itu, data domain lama tidak aman disalin langsung.
- `kelas` terbukti merupakan master sekolah/madrasah, bukan kelas belajar: contoh nilainya `MI YAPPI BADONGAN`, `MI YAPPI BALEHARJO`, dan seterusnya.
- `jurusan` terbukti merupakan master **status kepegawaian**: GTY non-sertifikasi, GTY sertifikasi, PNS, PTY, dan lain-lain.
- `periode` mencampur konsep periode SK (`Januari`, `Juli`) dengan penugasan/status (`Sebagai Kepala Madrasah`, `PNS diperbantukan`). Tabel ini tidak boleh dipindah sebagai satu master target.
- Terdapat tanggal nol MySQL pada beberapa master. Semua migrator berikut wajib mengubahnya menjadi `NULL` jika target nullable.
- Tidak ada data yang ditulis atau diubah pada kedua database selama tahap ini.

Status rekomendasi:

- **MIGRATE**: struktur dan makna cukup sepadan, tetapi tetap melalui migrator tervalidasi.
- **TRANSFORM**: satu sumber menuju struktur target yang berbeda atau memerlukan normalisasi/relasi baru.
- **MERGE**: digabung dengan sumber/target lain atau hanya menjadi atribut pada entitas target.
- **SKIP**: tidak dibawa karena sistem/framework, kosong, duplikat, atau tidak memiliki target bisnis yang layak.

## A. Master Data

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `tahun_ajaran` (4) | `academic_years` (5) | MERGE | `tahun -> name`, `active ON/OFF -> is_active`. Target unik pada `name`; semua source bertanda ON sehingga perlu aturan hanya satu tahun aktif sebelum migrasi. Cocokkan berdasarkan nama, bukan ID. |
| `periode` (5) | atribut `employee_assignments.decree_period` / normalisasi domain | MERGE | Bukan satu master homogen. `Januari/Juli` adalah periode SK; nilai lain adalah penugasan/status. Pecah berdasarkan nilai dan konteks pemakaian. |
| `bulan` (2) | tidak ada tabel; nilai periode semester | MERGE | `01=Januari-Juni`, `02=Juli-Desember`; gunakan sebagai lookup migrator, bukan tabel target. |
| `bulan_sk` (2) | atribut periode/tanggal SK | MERGE | `Januari/Juli`; gunakan untuk transformasi SK. |
| `jurusan` (8) | `employees.employment_status`, `employee_type`, kemungkinan `rank/grade` | MERGE | Nama legacy keliru; isinya status kepegawaian. Perlu kamus nilai eksplisit. Bukan `program_study`. |
| `ketugasan` (22) | `employee_positions` (22) dan/atau atribut assignment | MERGE | Nilai berupa tugas mengajar/jabatan. Cocokkan semantik dengan 22 posisi target melalui `code/name`; jangan mengandalkan ID. |
| `jenis_pembayaran` (15) | `payment_types` (2) | TRANSFORM | `pembayaran -> name`, `status -> is_active`, wajib `foundation_id`. Unique target `(foundation_id,name)`. Sebagian jenis spesifik tahun/SK/batik mungkin lebih tepat menjadi deskripsi invoice, bukan master permanen. |
| `jenis_mutasi` (3) | `employee_mutations.mutation_type` | MERGE | Target menyimpan string, tidak memiliki tabel jenis. Buat mapping tiga nilai ke vocabulary target. |
| `jenis_pemasukan` (6) | `treasury_transactions.category` | MERGE | Target tidak memiliki master kategori. Simpan sebagai kategori transaksi terstandar. |
| `jenis_pengeluaran` (9) | `treasury_transactions.category` | MERGE | Sama seperti pemasukan; arah transaksi berasal dari kolom debit/kredit lama. |
| `jenis_surat` (4) | `correspondence_types` (4) | MERGE | Cocokkan berdasarkan nama. Target sudah memiliki 4 seed; jangan duplikasi. Periksa perbedaan `Surat Permohonan` dengan tipe target. |
| `jenis_proposal` (2) | `proposal_requests.type_name` | MERGE | Target menyimpan snapshot nama jenis, tidak memiliki tabel jenis proposal. |
| `tahun_anggaran` (2) | `foundation_annual_reports.budget_year` dan data treasury | MERGE | Tidak ada master tahun anggaran target. Gunakan sebagai nilai tahun saat relasi sumber dapat dibuktikan. |
| `k_sertifikat` (2) | atribut sekolah `certificate_ownership` | MERGE | Lookup ya/tidak; target tidak memiliki master terpisah. |
| `phbnu` (2) | atribut sekolah `phbnu_status` | MERGE | Lookup ya/tidak; target tidak memiliki master terpisah. |
| `role` (3) | `roles` (4) | SKIP | Role user telah selesai dimigrasikan dengan mapping eksplisit; tabel legacy tidak digunakan lagi. |

## B. Sekolah/Madrasah

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `kelas` (62) | `schools` (2) | TRANSFORM | Master sekolah sebenarnya. `nama_kelas -> name`, `keterangan -> school_level`; wajib menambahkan `foundation_id`, NPSN, status, dan mapping `old_kelas_id -> school_id`. NPSN unik target sehingga data dari `users`, `updatesipinter`, dan sumber lain harus digabung dahulu. |
| `users` role 3 (62 dari 692) | `schools`, `memberships`, user admin yang sudah ada | MERGE | Kolom sekolah di user (`kelas_id`, nama, siswa, akreditasi, tanah, sertifikat, PHBNU) melengkapi `schools`; akun autentikasi tidak dibuat lagi. |
| `profile_lembaga` (1) | `foundations` (1) | MERGE | Profil lembaga tingkat yayasan: nama, alamat, telepon, logo. Cocokkan foundation yang sudah ada, jangan membuat yayasan kedua. |
| `sarpras` (39) | `schools` | MERGE | `kelas_id` tersimpan sebagai string; atribut akreditasi, kepala, tanah, sertifikat, PHBNU melengkapi sekolah. Perlu validasi referensi ke master `kelas`. |
| `updatesipinter` (60) | `sipinter_updates` (0) dan sebagian `schools` | TRANSFORM | `kelas` menunjuk sekolah; NPSN/alamat/status tanah dapat memperkaya `schools`, sedangkan dokumen permohonan/PCNU/PWNU/status masuk workflow `sipinter_updates`. Target unik `school_id`, jadi satu record terbaik per sekolah. |
| `data_siswa` (168) | `student_enrollments` (1) | TRANSFORM | `madrasah_id -> school_id`, `tahun_pelajaran -> academic_year`, kelas 1–9 dan total dipetakan langsung. Target unik `(school_id,academic_year)`; rekonsiliasi total dengan jumlah per tingkat. |
| `kesiswaan` (42) | `student_enrollments` | MERGE | Versi lama dengan semua angka disimpan sebagai varchar. Gabungkan dengan `data_siswa`; prioritaskan sumber yang lebih baru/lengkap dan jangan membuat pasangan sekolah-tahun ganda. |
| `siswa2` (0) | `student_enrollments`/`educator_recaps` | SKIP | Kosong dan strukturnya hanya nama madrasah + jumlah GTY sertifikasi; tidak ada data untuk dimigrasikan. |

## C. Guru/Pegawai

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `users` role 0/2 | `employees`, `employee_assignments`, `memberships` | TRANSFORM | User autentikasi sudah ada. Data NUPTK/NIP/lahir/alamat/pendidikan/prodi/TMT/status/ketugasan/sekolah menjadi domain pegawai. Gunakan user mapping `1833 -> 11`; skip 518. Unique target: `employee_code`, `nik`, `nip`, `nuptk`, `email`, `user_id`. |
| `tenaga` (42) | `educator_recaps` (1) | TRANSFORM | Rekap kategori tenaga per sekolah dan tahun, bukan profil individu. Gabungkan/rekonsiliasi dengan `data_tenaga_pendidik`. |
| `data_tenaga_pendidik` (116) | `educator_recaps` | TRANSFORM | Struktur paling dekat: ASN sertifikasi/non, yayasan sertifikasi/inpassing, nonsertifikasi, total. Target unik `(school_id,academic_year)`. |
| `usulan` (66) | `decree_proposals`, `employees`, `documents` | TRANSFORM | Ini pengajuan calon/pegawai untuk SK, bukan master pegawai murni. Cocokkan dahulu ke employee melalui identitas; file persyaratan masuk kolom proposal/dokumen; status workflow perlu mapping. |

## D. Keanggotaan/Relasi

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| Relasi `users.kelas_id` | `memberships` (3), `employees.school_id`, `employee_assignments` | TRANSFORM | Tidak ada tabel keanggotaan legacy. Bangun setelah school mapping tersedia. Admin sekolah dan guru perlu membership foundation/sekolah yang tepat. |
| `aktivasi` (18) | `employee_activity_requests` (1) | TRANSFORM | Data permohonan nonaktif: nama, sekolah legacy (`kelas`), tanggal nonaktif, surat, status. Wajib resolve employee, school, submitter bila tersedia. |

## E. Keuangan

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `tagihan` (714) | `payment_invoices` (0) | TRANSFORM | `user_id`, tahun ajaran, jenis, sekolah (`kelas_id`), nilai, status menjadi invoice. Target membutuhkan foundation/school dan memiliki unique `invoice_number` serta `(user_id,school_id,academic_year,description)`. Nomor invoice harus deterministik. |
| `payment` (625) | `payment_invoices` dan/atau histori pembayaran baru | TRANSFORM | Berisi pembayaran, metode, status, order/pdf, cicilan. Target saat ini tidak memiliki tabel payment transaction terpisah; perlu keputusan desain agar histori/cicilan tidak hilang atau tergandakan. Jangan migrasi sebelum model pembayaran final. |
| `invoices` (0) | `payment_invoices` | SKIP | Kosong. Struktur dapat dijadikan referensi lama, tetapi tidak ada record. |
| `bendaharas` (18) | `treasury_transactions` (0) | TRANSFORM | Satu baris memiliki sisi pemasukan/pengeluaran. Ubah menjadi `transaction_type`, category, amount, date, description, evidence path; wajib foundation. Validasi baris yang memiliki dua sisi atau tanpa nominal. |

## F. SK/Keputusan

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `sk` (1) | `decree_submissions` / `documents` | TRANSFORM | Record ringkas sekolah-tahun-bulan-file; perlu school mapping, tipe SK, nomor/tanggal derivasi, dan workflow target. |
| `sk_templates` (1) | `decree_templates` (0) | TRANSFORM | Legacy menyimpan builder HTML/CSS/content; target menyimpan file template (disk/path/original name). Tidak kompatibel untuk copy langsung; render/ekspor file atau arsipkan desain. |
| `sk_yayasan_documents` (262) | `documents`, kemungkinan `decree_submissions` | TRANSFORM | Arsip file SK per user/tahun/template. Resolve user (termasuk `1833 -> 11`), employee dan school; path unik target; checksum pada tahap file. |
| `sk_yayasan_settings` (5) | konfigurasi penomoran aplikasi/decree workflow | TRANSFORM | `periode_id`, pola dan counter tidak memiliki tabel target langsung. Perlu keputusan apakah menjadi application setting baru; jangan memaksakan ke submission. |
| `usulan` (66) | `decree_proposals` (1) | TRANSFORM | Sumber utama proposal SK baru/perpanjangan beserta lampiran. Dibahas juga pada guru/pegawai karena harus resolve employee. |

Target `decree_submission_types`, `decree_requirements`, `decree_submission_status_histories`, dan `approval_requests` adalah struktur workflow baru. Tidak ada source satu-banding-satu; tipe/requirement tetap berasal dari seed aplikasi, sedangkan history/approval hanya dibuat jika state legacy dapat direkonstruksi secara sah.

## G. Mutasi

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `mutasi` (19) | `employee_mutations` (1) | TRANSFORM | Resolve employee berdasarkan kode/nama, sekolah asal/tujuan berdasarkan mapping nama/ID, normalisasi jenis dan tanggal efektif. Target mendukung snapshot nama bila employee/sekolah tidak ditemukan, tetapi orphan harus dilaporkan. |
| `jenis_mutasi` (3) | `employee_mutations.mutation_type` | MERGE | Lookup nilai saja; tidak dibuat sebagai tabel target. |

`mutation_requirements` adalah konfigurasi workflow baru dan tidak diisi dari histori mutasi.

## H. Presensi

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `attendance_settings` (58) | `attendance_settings` (1) | TRANSFORM | Legacy unik per `kelas_id` (sekolah); target unik per `school_id`. Jam masuk/pulang dapat dipetakan, tetapi enable flags, GPS max, fake-GPS dan polygon tidak seluruhnya punya kolom sepadan. |
| `attendances` (469) | `attendance_records` (1) | TRANSFORM | Legacy dapat memiliki baris `datang` dan `pulang`; target unik `(employee_id,attendance_date)` dan menyimpan keduanya dalam satu baris. Wajib merge per user+tanggal, resolve employee+school, serta normalisasi status/GPS/selfie. |
| `attendance_permissions` (4) | `attendance_leave_requests` (0) | TRANSFORM | Kategori `terlambat/sakit/tidak_masuk/tugas_dinas/cuti` perlu mapping ke `leave_type`; resolve user, employee, school dan reviewer; status workflow dipetakan. |

## I. Persuratan

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `persuratan` (24) | `correspondence_requests` (1) | TRANSFORM | `kelas -> school`, `jenis -> correspondence_type/type_name`, file permohonan dan file ACC menjadi request/response path, status menjadi process status. |
| `jenis_surat` (4) | `correspondence_types` (4) | MERGE | Target sudah diseed; cocokkan nama, jangan duplikasi ID. |
| `surats` (0) | tidak ada data | SKIP | Hanya ID/timestamp dan kosong. |

## J. Proposal

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `proposal` (16) | `proposal_requests` (1) | TRANSFORM | Resolve school dari `kelas_id`; jenis menjadi snapshot type, nominal ke amount, data rekening dipisah, proposal/approve file menjadi request/response, status dan alasan ditransformasi. |
| `jenis_proposal` (2) | `proposal_requests.type_name` | MERGE | Lookup nama; target tidak memiliki master jenis proposal. |

`proposal_requirements` adalah konfigurasi baru dan tidak ditimpa oleh histori legacy.

## K. Batik

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `stok_batik` (5) | `batik_products` (3) | TRANSFORM | `produk`, stok, harga menjadi produk per foundation. Target unik `(foundation_id,name)`; target juga membutuhkan audience, size, urutan, status. |
| `batik_maarif` (83) | `batik_orders` (3) | TRANSFORM | `asal_sekolah -> school`, kuantitas siswa/guru perlu dipecah menurut produk/ukuran; total tagihan direkonsiliasi dengan harga dan quantity. `penerima` menjadi recipient. |

## L. Program Kerja/Yayasan

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `aplikasi` (1) | `application_settings` (1) | MERGE | Mapping kuat: owner, alamat, telepon, judul, nama aplikasi, logo, copyright, versi, token WA, server/client key. Jangan menimpa setting target tanpa perbandingan nilai dan perlindungan secret. Kolom info modul tidak punya target langsung. |
| `profile_lembaga` (1) | `foundations` (1) | MERGE | Profil yayasan; gabungkan ke foundation existing. |
| `program_kerja` (0) | `foundation_annual_reports` atau dokumen program | SKIP | Kosong; beda dengan `program_kerjas`. |
| `program_kerjas` (0) | `foundation_work_programs` (0) | SKIP | Kosong; secara struktur cocok tetapi tidak ada record. |
| `laporan_tahunan` (0) | `foundation_annual_reports` (0) | SKIP | Kosong. |
| `laporan_tahunans` (0) | `foundation_annual_reports` (0) | SKIP | Duplikat evolusi schema dan kosong. |
| `agenda_kesekretariatans` (1) | `secretariat_agendas` (0) | MIGRATE | Struktur sepadan: kegiatan, tanggal, petugas, keterangan/status, catatan; tambahkan foundation. Mapping status perlu aturan karena source memakai `keterangan`. |
| `phbnu` (2) | atribut sekolah | MERGE | Master lookup, bukan board member. |

Tidak ditemukan source terstruktur untuk `foundation_board_members`; data pengurus mungkin berada di luar tabel domain atau perlu input manual. Jangan menyimpulkan `phbnu` sebagai pengurus.

## M. Dokumen dan Modul

| Source SIDIKMA (baris) | Target PostgreSQL (baris) | Status | Catatan |
|---|---|---|---|
| `modul` (72) | `learning_modules` (0) | TRANSFORM | Struktur dekat: kelas, jenis, semester, mapel, bab, path. Target membutuhkan foundation, original name, uploaded_at/by. File fisik ditangani Tahap 12. |
| Seluruh kolom file pada `users`, `aktivasi`, `mutasi`, `persuratan`, `proposal`, `usulan`, `sk*`, `bendaharas`, `updatesipinter` | `documents` dan/atau kolom path modul | TRANSFORM | Pada tahap domain hanya catat path/mapping. Migrasi file harus copy, cek existence/checksum, dan idempotent pada Tahap 12. Unique target `documents.path` berpotensi bentrok. |

## N. Sistem/Tidak Perlu Dimigrasikan

| Source SIDIKMA (baris) | Target PostgreSQL | Status | Catatan |
|---|---|---|---|
| `migrations` (33) | `migrations` | SKIP | Riwayat schema aplikasi lama tidak berlaku untuk aplikasi baru. |
| `failed_jobs` (0) | `failed_jobs` | SKIP | Queue lama tidak dijalankan ulang. |
| `password_reset_tokens` (66) | `password_reset_tokens` | SKIP | Token sensitif/kedaluwarsa; user meminta tidak membawa sesi/token lama. |
| `personal_access_tokens` (0) | tidak perlu | SKIP | Kosong dan token aplikasi lama tidak sah di aplikasi baru. |
| `mm_logs` (12.505) | `activity_log` (72) | SKIP | Format log legacy berbeda dan volumenya dominan. Simpan database lama sebagai arsip audit read-only; jangan mencampur dengan audit baru kecuali ada kebutuhan legal terpisah. |
| `broadcasts` (24) | `notifications` (0), tidak ada model broadcast | SKIP | Broadcast historis tidak cocok dengan notifikasi per-user target. Arsipkan di source; jangan kirim ulang. |
| `role` (3) | `roles` | SKIP | Migrasi role user sudah selesai. |
| Schema MySQL `laravel` (9 tabel): `cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`, `migrations`, `password_reset_tokens`, `sessions`, `users` | tabel framework target | SKIP | Ini schema lain yang terlihat oleh koneksi, bukan domain `ABC`; jangan dicampur dengan SIDIKMA. |
| PostgreSQL `untitled_table_254` (0) | tidak ada source | SKIP / REVIEW | Tabel kosong tanpa model/migration yang ditemukan. Jangan dihapus pada tahap ini; tandai untuk audit schema terpisah. |

## Mapping Kolom Inti

### Sekolah

| Sumber | Target | Aturan |
|---|---|---|
| `kelas.id` | mapping `old_school_id -> schools.id` | Pertahankan ID hanya jika tidak bentrok dan seluruh relasi bisa konsisten; target saat ini sudah mempunyai ID. Mapping eksplisit lebih aman. |
| `kelas.nama_kelas`, `users.nama_kelas` | `schools.name` | Normalisasi spasi/case, lalu cocokkan. |
| `updatesipinter.npsn` | `schools.npsn` | Validasi tepat 8 digit dan unique. |
| `kelas.keterangan` | `schools.school_level` | Ekstrak MI/MTs/MA/RA dari nilai. |
| `users.alamat`, `updatesipinter.alamat` | `schools.address` | Tentukan prioritas sumber berdasarkan kelengkapan/updated_at. |
| `users.akreditasi`, `sarpras.status_akreditasi` | `schools.accreditation_status` | Rekonsiliasi bila berbeda. |
| `users.masaakreditasi`, `sarpras.masa_akreditasi` | `schools.accreditation_expiry_year` | Parse tahun; nilai invalid dilaporkan. |
| tanah/sertifikat/atas nama/PHBNU dari `users`, `sarpras`, `updatesipinter` | kolom profil `schools` | Gunakan lookup `k_sertifikat`/`phbnu`, jangan simpan ID legacy sebagai teks. |

### Pegawai

| Sumber | Target | Aturan |
|---|---|---|
| `users.id` | `employees.user_id` | User sudah ada; wajib mapping 1833→11 dan skip 518. |
| `nama_lengkap` | `employees.name` | Trim tanpa mengubah identitas. |
| `nis` | `employees.employee_code` | Wajib unique; jika kosong/duplikat perlu kode deterministik yang terdokumentasi. |
| `nip`, `nuptk`, `email` | kolom sama | String kosong menjadi NULL sebelum unique check. |
| `jurusan_id/nama_jurusan` | `employment_status`/`employee_type` | Gunakan kamus 8 status, bukan jurusan akademik. |
| `p_studi` | `program_study` | Nilai program studi sebenarnya. |
| `ptt_lulus` | `last_education` | Verifikasi makna dengan data aktual sebelum final. |
| `pangkat_golongan` | `rank`, `grade` | Pecah hanya dengan parser tervalidasi; jika ambigu simpan nilai di field yang paling tepat dan laporkan. |
| `tmt` | assignment `start_date` | Tanggal nol menjadi NULL; target assignment start date wajib sehingga perlu kebijakan invalid. |
| `ketugasan` | `employee_position_id` | Mapping melalui master posisi terverifikasi. |
| `kelas_id` | `school_id` | Gunakan school mapping reusable. |

### Keuangan

| Sumber | Target | Aturan |
|---|---|---|
| `tagihan.user_id` | `payment_invoices.user_id` | Mapping user eksplisit. |
| `tagihan.kelas_id` | `school_id` | Mapping sekolah. |
| `tagihan.thajaran_id` | `academic_year` | Resolve `tahun_ajaran.id -> tahun`, bukan copy ID. |
| `tagihan.nilai` | `amount` | Numeric dan non-negatif; rekonsiliasi total. |
| `tagihan.status`, `payment.status` | invoice `status`, `paid_at` | Butuh matriks status eksplisit. |
| `payment.order_id/pdf_url/installment_*` | belum ada target lengkap | Gap schema; jangan hilangkan histori. Putuskan tabel pembayaran/histori sebelum Tahap 5. |

### Presensi

| Sumber | Target | Aturan |
|---|---|---|
| `attendances.user_id` | `user_id`, lalu resolve `employee_id` | User tanpa employee menjadi invalid/orphan. |
| `kelas_id` | `school_id` | Mapping sekolah. |
| dua baris `check_type=datang/pulang` | satu `attendance_records` | Group per employee+date; pilih check-in/out sesuai tipe dan timestamp. |
| koordinat/GPS/selfie per event | kolom check-in/check-out target | Jangan menaruh data pulang pada kolom masuk. |

## Foreign Key dan Unique Key Target yang Mengendalikan Urutan

- `schools` bergantung pada `foundations`; `schools.npsn` unik.
- `memberships` bergantung pada user, foundation, dan optional school.
- `employees` bergantung pada foundation, optional school dan user; `user_id`, email, NIP, NUPTK, NIK, employee code unik.
- `employee_assignments` bergantung pada employee, position, foundation, school.
- Rekap siswa/tenaga unik per `(school_id, academic_year)`.
- `attendance_settings` dan `sipinter_updates` unik per school.
- `attendance_records` unik per `(employee_id, attendance_date)`.
- `payment_types` dan `batik_products` unik per `(foundation_id,name)`.
- `payment_invoices.invoice_number` unik serta unique account bill `(user_id,school_id,academic_year,description)`.
- `foundation_annual_reports` unik per `(foundation_id,budget_year)`.
- `documents.path`, `decree_submissions.submission_number`, dan correction `request_number` unik.
- Seluruh kolom actor (`submitted_by`, `processed_by`, `uploaded_by`, `reviewed_by`, dan lain-lain) harus memakai mapping user, termasuk 1833→11.

## Dependency dan Urutan Migrasi yang Direkomendasikan

1. **Lookup/kamus migrasi**: academic year, vocabulary status, position mapping, jenis surat/payment/mutasi; tidak semua menjadi tabel target.
2. **Foundation/application profile**: merge `profile_lembaga` dan `aplikasi` ke record existing setelah review field.
3. **Schools + school mapping**: gabungkan `kelas`, user role 3, `sarpras`, dan `updatesipinter`.
4. **User relationship layer**: memberships admin sekolah; user sudah selesai dimigrasi.
5. **Employees + employee mapping**: user role 0/2, position, assignment, membership.
6. **School aggregates**: student enrollments dan educator recaps.
7. **Workflow employee**: aktivasi dan usulan SK.
8. **Finance masters**, kemudian invoice/tagihan, baru payment/histori dan treasury.
9. **SK/decree**, setelah employee+school mapping tersedia.
10. **Mutasi**, setelah employee dan sekolah lengkap.
11. **Presensi settings**, lalu leave requests dan attendance records.
12. **Persuratan dan proposal**.
13. **Yayasan/program/agenda**, batik, learning modules.
14. **Metadata dokumen**, kemudian file fisik pada tahap khusus.
15. **Reconciliation global** dan automated tests.

## Risiko dan Keputusan yang Harus Diselesaikan Sebelum Tahap 2+

1. Target saat ini sudah berisi seed/sample pada banyak tabel. Semua migrator harus membedakan seed yang harus dipertahankan dari data legacy yang sama.
2. Karena source tidak mempunyai FK, setiap ID relasi harus divalidasi manual; tidak boleh diasumsikan valid.
3. School identity berasal dari sedikitnya empat sumber (`kelas`, user role 3, `sarpras`, `updatesipinter`) dan mungkin berkonflik pada nama/NPSN.
4. `tahun_ajaran` memiliki empat record seluruhnya `ON`, sedangkan model target mengharapkan konsep tahun aktif tunggal.
5. `payment` memiliki informasi transaksi/cicilan yang tidak tertampung penuh oleh schema target. Tahap keuangan memerlukan keputusan schema/business flow sebelum insert.
6. Attendance legacy menyimpan event masuk/pulang terpisah, sedangkan target satu record harian. Duplicate/event tidak lengkap harus dilaporkan.
7. Banyak path file belum membuktikan file fisiknya tersedia. Jangan menganggap record dokumen sukses sebelum tahap inventarisasi file.
8. `remember_token`, session, reset token, queue, cache, dan job lama harus tetap tidak dimigrasikan.
9. `mm_logs` disarankan tetap sebagai arsip read-only, bukan diubah menjadi activity log baru.
10. Semua migrator selanjutnya harus memproses batch/chunk, idempotent, dry-run dahulu, transaction pada target, statistik dan validasi pascamigrasi.

## Kesimpulan Tahap 1

Inventarisasi seluruh tabel source dan target telah selesai. Tidak ada tabel domain besar yang aman untuk bulk-copy langsung. Dependency utama adalah:

`academic year/foundation → schools → memberships/employees/assignments → seluruh transaksi dan workflow`.

Tahap 2 belum dijalankan. Kandidat master yang layak dianalisis lebih lanjut adalah `tahun_ajaran`, mapping `ketugasan -> employee_positions`, `jenis_pembayaran -> payment_types`, serta lookup jenis surat/mutasi. Tabel `kelas` ditunda ke tahap sekolah karena secara faktual merupakan master sekolah, bukan master kelas.

## Hasil Tahap 2 — Master Data

Tahap 2 diselesaikan pada 13 Agustus 2026 melalui command `sidikma:migrate-master-data` yang mendukung `--dry-run`, konfirmasi/`--force`, transaksi PostgreSQL, statistik, mapping ID, validasi konflik/invalid, dan validasi pascamigrasi.

- Empat tahun ajaran source dipetakan ke target existing ID 1–4; tidak ada insert atau duplikasi.
- Seluruh 22 ketugasan dipetakan berdasarkan nama/kode ke 22 posisi target. ID 17–22 legacy tidak sama dengan target sehingga mapping semantik wajib dipakai pada tahap employee.
- Lima belas jenis pembayaran dikonsolidasikan menjadi empat jenis umum: `IURAN` (ID 1), `Pembayaran Batik` (ID 2), `Pembayaran SK` (ID 3), dan `Pembayaran Buku Ke-NU-an` (ID 4).
- Jenis surat dipetakan/ditambah menjadi `Surat Rekomendasi` (ID 2), `Surat Perintah Tugas` (ID 3), `Surat Keterangan` (ID 6), dan `Surat Permohonan` (ID 7).
- Dry-run pascamigrasi: eligible 0, konflik 0, invalid 0.
- Tidak ditemukan duplikasi payment type, academic year, atau position code. Sequence payment type berada di 4 dan correspondence type di 7.
- Unit test mapping: 9 tests, 12 assertions, semuanya lulus.

## Hasil Tahap 3 — Sekolah/Madrasah

Tahap 3 diselesaikan pada 13 Agustus 2026 melalui command `sidikma:migrate-schools` dengan mode `--dry-run`, transaksi target, statistik konflik/invalid, mapping old school ID ke target, dan validasi pascamigrasi.

- 62 master `kelas` terbukti berpasangan tepat dengan 62 akun admin sekolah role 3.
- 60 sekolah baru dibuat; Baleharjo (target ID 1) dan Wareng (target ID 2) dipertahankan sebagai existing.
- Total target menjadi 62 sekolah: 52 MI, 6 MTs, dan 4 SMP.
- Seluruh 62 admin sekolah memperoleh satu membership aktif ke foundation ID 13 dan sekolahnya masing-masing.
- NPSN memakai `updatesipinter.npsn` sebagai prioritas dan `users.nis` sebagai fallback. NPSN SIPINTER Semoyo yang salah panjang tidak digunakan; fallback valid `20402263` menghasilkan school ID 43.
- Profil digabung dari akun sekolah dan `sarpras`; `sarpras.kelas_id` ternyata nama sekolah dan seluruh 39 record cocok berdasarkan nama ternormalisasi.
- Nilai luas tanah dinormalisasi secara konservatif. Nilai masa akreditasi yang bukan tahun empat digit dibiarkan NULL, bukan ditebak.
- Dry-run pascamigrasi: sekolah eligible 0, existing 62, konflik 0, invalid 0; membership eligible 0, existing 62.
- Tidak ada NPSN duplikat atau membership orphan. Sequence sekolah dan max ID sama-sama 62.
- Unit test parser sekolah: 9 tests, 14 assertions, semuanya lulus.

## Hasil Tahap 4 — Guru/Pegawai

Tahap 4 diselesaikan pada 13 Agustus 2026 melalui command `sidikma:migrate-employees` dengan dry-run, transaksi PostgreSQL, mapping sekolah/posisi, fallback konservatif, idempotensi, dan reconciliation pascamigrasi.

- 626 akun SIDIKMA role 0/2 menghasilkan 626 profil employee, 626 assignment utama aktif, dan 622 membership sekolah.
- Empat employee tanpa sekolah legacy tetap berada pada foundation tanpa dipaksa ke sekolah; relasi nullable target dipertahankan.
- Hasil tipe: 583 guru dan 43 pegawai. Status: 617 aktif dan 9 nonaktif, sesuai status user legacy.
- Seluruh status kepegawaian `jurusan_id` dipetakan eksplisit ke vocabulary model target. Seluruh `ketugasan` dipetakan berdasarkan code/name posisi, bukan ID legacy.
- 34 NIS kosong, placeholder, atau duplikat memakai kode deterministik `SIDIKMA-{user_id}`. Tidak ada duplicate employee code.
- NUPTK hanya dipertahankan bila tepat 16 digit. Sebanyak 318 nilai kosong/placeholder/format gabungan menjadi NULL; tidak ada duplicate NUPTK.
- Empat TMT kosong memakai tanggal legacy pertama yang valid dari `created_at` lalu `updated_at`, dan assignment diberi catatan fallback.
- Migrasi user khusus tetap berlaku; source role 0/2 tidak mencakup ID 1833, dan ID 518 tetap dikecualikan.
- Total target menjadi 628 employee termasuk dua data existing. Sequence dan max ID sama-sama 653.
- Tidak ada orphan employee-user atau assignment employee/school.
- Dry-run pascamigrasi: eligible 0, existing 626, assignment existing 626, membership existing 622, konflik 0, invalid 0.
- Unit test transformasi employee: 4 tests, 13 assertions, semuanya lulus.
