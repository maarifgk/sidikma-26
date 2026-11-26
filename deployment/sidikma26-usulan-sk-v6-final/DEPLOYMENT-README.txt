SIDIKMA 26 - Usulan SK Baru v6 FINAL

Ruang lingkup
-------------
Paket ini mempertahankan business logic release v5 dan menyempurnakan tampilan detail action "Lihat" agar mengikuti referensi UI/UX SIDIKMA 26. Tidak ada migration, seeder, perubahan database, route, policy, permission, service, vendor, atau .env.

Isi paket
---------
Paket berisi file fitur Usulan SK Baru yang diperlukan untuk konsistensi release v5, dua file UI yang diperbarui, panduan rollback, serta manifest SHA-256.

Instalasi manual
----------------
1. Backup file target pada server.
2. Upload isi paket ke root project Laravel dengan mempertahankan relative path.
3. Ganti hanya file yang terdaftar pada bagian "Daftar file update".
4. Jalankan prosedur cache/view clear yang disetujui pengelola hosting jika diperlukan.
5. Verifikasi halaman Usulan SK Baru > Lihat secara read-only.

Jika release v5 sudah terpasang
-------------------------------
Untuk update UI saja, ganti hanya dua file berikut:
- app/Filament/Resources/DecreeProposals/Schemas/DecreeProposalInfolist.php
- resources/views/filament/shared/decree-proposal-detail-styles.blade.php

File lain dalam paket disertakan sebagai snapshot fitur v5 untuk konsistensi deployment. Jangan mengganti modul yang tidak tercantum dalam paket.

Daftar file update
------------------
- app/Filament/Resources/DecreeProposals/DecreeProposalResource.php
- app/Filament/Resources/DecreeProposals/Pages/CreateDecreeProposal.php
- app/Filament/Resources/DecreeProposals/Pages/ListDecreeProposals.php
- app/Filament/Resources/DecreeProposals/Schemas/DecreeProposalEditForm.php
- app/Filament/Resources/DecreeProposals/Schemas/DecreeProposalForm.php
- app/Filament/Resources/DecreeProposals/Schemas/DecreeProposalInfolist.php
- app/Filament/Resources/DecreeProposals/Tables/DecreeProposalsTable.php
- app/Http/Controllers/DecreeProposalDocumentController.php
- app/Models/DecreeProposal.php
- app/Policies/DecreeProposalPolicy.php
- app/Services/DecreeProposalApprovalService.php
- app/Services/DecreeProposalEditService.php
- app/Services/DecreeProposalRejectionService.php
- resources/views/filament/shared/decree-proposal-detail-styles.blade.php

Rollback
--------
Ikuti ROLLBACK.txt. Rollback harus memakai backup file aplikasi yang dibuat sebelum upload dan tidak boleh mengubah data produksi.

Audit read-only proposal legacy
-------------------------------
Perintah berikut hanya membaca tiga proposal SMP Pembangunan Ponjong, memanggil legacyEligibility(), dan tidak melakukan save, create, update, delete, insert, migration, atau seeding:

php artisan tinker --execute='$needle="SMP Pembangunan Ponjong"; $rows=App\Models\DecreeProposal::query()->where("status", App\Models\DecreeProposal::STATUS_SUBMITTED)->where(function($q)use($needle){$q->where("candidate_school","like","%{$needle}%")->orWhereJsonContains("legacy_snapshot->school_name",$needle)->orWhereHas("employee.school",fn($sq)=>$sq->where("name","like","%{$needle}%"));})->with(["employee.user","employee.school","employee.currentAssignment.position"])->get(); $service=app(App\Services\DecreeProposalApprovalService::class); foreach($rows as $p){$e=$service->legacyEligibility($p); echo json_encode(["proposal_id"=>$p->id,"candidate_name"=>$p->displayName(),"school"=>$p->displaySchoolName(),"eligible"=>$e["eligible"],"reasons"=>$e["reasons"]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;}'

Jika hasil kosong, ulangi pemeriksaan read-only dengan nama sekolah persis seperti yang tercatat pada data hosting. Jangan mengubah perintah tersebut menjadi perintah mutasi.

Verifikasi hash
---------------
Manifest pada MANIFEST-SHA256.txt berisi hash setiap file source di paket. Hash ZIP final dicatat pada laporan release setelah arsip selesai dibuat. Target pemeriksaan: HASH_MISMATCH_COUNT=0 dan LINT_FAIL_COUNT=0.

Catatan visual
--------------
Visual review browser tidak dijalankan bila server lokal, sesi login, atau data testing terisolasi tidak tersedia secara aman. Status visual harus dibedakan dari validasi source dan hash.
