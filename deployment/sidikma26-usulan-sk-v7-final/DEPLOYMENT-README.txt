SIDIKMA 26 - Usulan SK Baru v7 FINAL

Ruang lingkup
-------------
Paket ini mempertahankan One Screen Detail View dan seluruh business logic release v6. Perubahan aplikasi terhadap v6 hanya pada:
- resources/views/filament/shared/decree-proposal-detail-styles.blade.php

Tidak ada migration, seeder, perubahan database, route, policy, permission, service, vendor, dependency, atau .env.

Jika release v6 sudah terpasang
-------------------------------
Ganti hanya file berikut:
- resources/views/filament/shared/decree-proposal-detail-styles.blade.php

DecreeProposalInfolist.php dan file fitur lain tidak berubah berdasarkan perbandingan SHA-256 terhadap source aktif. Tidak perlu upload ulang seluruh modul apabila v6 sudah terpasang.

Instalasi manual
----------------
1. Backup file target pada server.
2. Upload isi paket ke root project Laravel dengan mempertahankan relative path.
3. Untuk instalasi v6, ganti hanya stylesheet yang disebutkan di atas.
4. Jalankan prosedur cache/view clear yang disetujui pengelola hosting jika diperlukan.
5. Verifikasi halaman Usulan SK Baru > Lihat secara read-only.

Daftar file paket
----------------
Paket berisi snapshot 14 file fitur Usulan SK Baru dari source aktif, DEPLOYMENT-README.txt, ROLLBACK.txt, dan MANIFEST-SHA256.txt. File fitur disertakan untuk konsistensi paket; daftar upload minimal terhadap v6 tetap hanya stylesheet.

Rollback
--------
Ikuti ROLLBACK.txt. Rollback harus memakai backup file aplikasi yang dibuat sebelum upload dan tidak boleh mengubah data produksi.

Audit read-only proposal legacy
-------------------------------
Perintah berikut membaca proposal SMP Pembangunan Ponjong, memanggil legacyEligibility(), dan tidak melakukan save, create, update, delete, insert, migration, atau seeding:

php artisan tinker --execute='$needle="SMP Pembangunan Ponjong"; $rows=App\Models\DecreeProposal::query()->where("status", App\Models\DecreeProposal::STATUS_SUBMITTED)->where(function($q)use($needle){$q->where("candidate_school","like","%{$needle}%")->orWhereJsonContains("legacy_snapshot->school_name",$needle)->orWhereHas("employee.school",fn($sq)=>$sq->where("name","like","%{$needle}%"));})->with(["employee.user","employee.school","employee.currentAssignment.position"])->get(); $service=app(App\Services\DecreeProposalApprovalService::class); foreach($rows as $p){$e=$service->legacyEligibility($p); echo json_encode(["proposal_id"=>$p->id,"candidate_name"=>$p->displayName(),"school"=>$p->displaySchoolName(),"eligible"=>$e["eligible"],"reasons"=>$e["reasons"]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;}'

Jika hasil kosong, ulangi pemeriksaan read-only dengan nama sekolah persis seperti data hosting. Jangan mengubah perintah menjadi perintah mutasi.

Verifikasi hash
---------------
MANIFEST-SHA256.txt berisi hash setiap file dalam paket. Pemeriksaan V7 menargetkan HASH_MISMATCH_COUNT=0, SOURCE_MISMATCH_COUNT=0, ZIP_CONTENT_MISMATCH_COUNT=0, LINT_FAIL_COUNT=0, dan FORBIDDEN_FILE_COUNT=0.
