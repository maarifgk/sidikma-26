SIDIKMA 26 - Usulan SK Baru v5 FINAL

Upload
------
Upload the application files in this package to the project root, preserving relative paths. Do not upload .env, database files, vendor, tests, credentials, preview files, or debugging files. No route patch is included because the existing decree-proposal document route is unchanged.

If release v4 is already installed
-----------------------------------
For the redesign-only update, replace only these two files after verifying the release v4 application files are already present:
- app/Filament/Resources/DecreeProposals/Schemas/DecreeProposalInfolist.php
- resources/views/filament/shared/decree-proposal-detail-styles.blade.php

The full package also contains the release v4 workflow files for consistency. Do not replace unrelated modules.

Rollback
--------
Restore the previous versions of the same application files from the hosting backup, then run the hosting-approved cache/view clear procedure. Do not change production data as part of rollback.

Read-only audit for the three SMP Pembangunan Ponjong proposals
---------------------------------------------------------------
Run from the Laravel project root on the hosting terminal. This uses the application models and legacyEligibility() and performs no save, create, update, delete, insert, migration, or seeding operation. It does not print passwords, hashes, tokens, or secrets.

php artisan tinker --execute='$needle="SMP Pembangunan Ponjong"; $rows=App\Models\DecreeProposal::query()->where("status", App\Models\DecreeProposal::STATUS_SUBMITTED)->where(function($q)use($needle){$q->where("candidate_school","like","%{$needle}%")->orWhereJsonContains("legacy_snapshot->school_name",$needle)->orWhereHas("employee.school",fn($sq)=>$sq->where("name","like","%{$needle}%"));})->with(["employee.user","employee.school","employee.currentAssignment.position"])->get(); $service=app(App\Services\DecreeProposalApprovalService::class); foreach($rows as $p){$e=$service->legacyEligibility($p); $employee=$e["employee"]; $assignment=$e["assignment"]; echo json_encode(["proposal_id"=>$p->id,"candidate_name"=>$p->displayName(),"school"=>$p->displaySchoolName(),"status"=>$p->status,"employee_id"=>$p->employee_id,"user_id"=>$employee?->user_id,"identity_employee_code"=>$employee?->employee_code,"identity_email"=>$employee?->email,"identity_matches"=>count(array_filter($e["reasons"],fn($r)=>str_contains(strtolower($r),"identitas")))===0,"school_foundation_match"=>($employee?->school_id===$p->candidateSchoolId()&&$employee?->foundation_id===$p->candidateFoundationId()),"membership"=>$e["membership"]?"active":"missing","employee_assignment"=>$assignment?"active-primary":"missing","eligible"=>$e["eligible"],"reasons"=>$e["reasons"]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;}'

If the output is empty, do not assume there are no proposals. Repeat the same read-only check using the exact school name shown by the hosting data, without changing the command to a mutating command.

Production verification
-----------------------
This package does not prove the production file version, permission cache, or the three proposal records. Verify those separately after upload using read-only checks.
