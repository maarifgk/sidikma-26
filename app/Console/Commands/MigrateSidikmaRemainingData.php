<?php

namespace App\Console\Commands;

use App\Support\SidikmaMasterData;
use App\Support\SidikmaSchoolData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateSidikmaRemainingData extends Command
{
    protected $signature = 'sidikma:migrate-remaining {--dry-run} {--force} {--only= : Batasi modul, misalnya attendance} {--target-connection= : Koneksi database target, misalnya mysql atau pgsql}';
    protected $description = 'Migrasi modul SIDIKMA tersisa secara idempoten';

    private array $counts = [];
    private array $warnings = [];

    public function handle(): int
    {
        try {
            $rows = $this->build();
        } catch (Throwable $e) {
            $this->error($e->getMessage()); return self::FAILURE;
        }
        $this->table(['Modul', 'Sumber', 'Baru', 'Sudah ada', 'Lewati'], array_map(fn ($k, $v) => [$k, ...$v], array_keys($this->counts), $this->counts));
        foreach ($this->warnings as $warning) $this->warn($warning);
        if ($this->option('dry-run')) { $this->info('DRY RUN SELESAI — PostgreSQL tidak diubah.'); return self::SUCCESS; }
        if (! $this->option('force') && ! $this->confirm('Migrasikan seluruh data eligible?')) return self::SUCCESS;
        try {
            $targetConnection = $this->option('target-connection') ?: env('SIDIKMA_TARGET_CONNECTION', 'pgsql');
            DB::connection($targetConnection)->transaction(fn () => $this->insert($rows, $targetConnection));
        } catch (Throwable $e) {
            report($e); $this->error('Gagal; transaksi di-rollback: '.$e->getMessage()); return self::FAILURE;
        }
        $this->counts = []; $this->warnings = []; $this->build();
        if (collect($this->counts)->sum(fn ($v) => $v[1]) > 0) { $this->error('Validasi pascamigrasi gagal.'); return self::FAILURE; }
        $this->info('MIGRASI MODUL TERSISA SELESAI.'); return self::SUCCESS;
    }

    private function build(): array
    {
        $m = DB::connection('mysql_sidikma'); $targetConnection = $this->option('target-connection') ?: env('SIDIKMA_TARGET_CONNECTION', 'pgsql'); $p = DB::connection($targetConnection);
        $foundation = $p->table('foundations')->whereNull('deleted_at')->sole();
        $classNames = $m->table('kelas')->pluck('nama_kelas', 'id');
        $schools = $p->table('schools')->whereNull('deleted_at')->get()->keyBy(fn ($s) => SidikmaMasterData::normalized($s->name));
        $school = fn ($id) => ($n = $classNames->get((int) $id)) ? $schools->get(SidikmaMasterData::normalized($n)) : null;
        $employees = $p->table('employees')->whereNull('deleted_at')->whereNotNull('user_id')->get()->keyBy('user_id');
        $users = $p->table('users')->pluck('id')->flip(); $now = now(); $out = [];

        $settings = $m->table('attendance_settings')->get(); $existing = $p->table('attendance_settings')->pluck('school_id')->flip(); $new=[]; $skip=0;
        foreach ($settings as $r) { $s=$school($r->kelas_id); if(!$s){$skip++;continue;} if($existing->has($s->id))continue; $new[]=['school_id'=>$s->id,'check_in_start'=>$r->check_in_time,'late_after'=>date('H:i:s',strtotime($r->check_in_time.' +'.(int)$r->late_tolerance_minutes.' minutes')),'check_out_start'=>$r->check_out_time,'require_location'=>true,'require_selfie'=>(bool)$r->require_selfie,'is_active'=>(bool)($r->enable_check_in||$r->enable_check_out),'geofence_polygon'=>$r->geofence_polygon,'created_at'=>$r->created_at?:$now,'updated_at'=>$r->updated_at?:$now]; }
        $out['attendance_settings']=$new; $this->stat('Pengaturan absensi',$settings->count(),count($new),$settings->count()-count($new)-$skip,$skip);

        $attendance = $m->table('attendances')->orderBy('id')->get()->groupBy(fn($r)=>$r->user_id.'|'.$r->attendance_date); $existing=$p->table('attendance_records')->get()->keyBy(fn($r)=>$r->employee_id.'|'.$r->attendance_date); $new=[];$skip=0;
        foreach($attendance as $key=>$group){$r=$group->last();$e=$employees->get($r->user_id);$s=$school($r->kelas_id);if(!$e||!$s||!$users->has($r->user_id)){$skip+=$group->count();continue;}if($existing->has($e->id.'|'.$r->attendance_date))continue;$in=$group->first(fn($x)=>$x->check_type==='datang');$outRow=$group->first(fn($x)=>$x->check_type==='pulang');$status=$group->contains(fn($x)=>$x->status==='ditolak')?'rejected':($group->contains(fn($x)=>$x->status==='terlambat')?'late':'present');$new[]=['user_id'=>$r->user_id,'employee_id'=>$e->id,'school_id'=>$s->id,'attendance_date'=>$r->attendance_date,'status'=>$status,'check_in_at'=>$in?->check_in_at?:$in?->checked_at,'check_out_at'=>$outRow?->check_out_at?:$outRow?->checked_at,'check_in_latitude'=>$in?->check_in_latitude?:$in?->latitude,'check_in_longitude'=>$in?->check_in_longitude?:$in?->longitude,'check_out_latitude'=>$outRow?->check_out_latitude?:$outRow?->latitude,'check_out_longitude'=>$outRow?->check_out_longitude?:$outRow?->longitude,'check_in_accuracy'=>$in?->check_in_gps_accuracy?:$in?->gps_accuracy,'check_out_accuracy'=>$outRow?->check_out_gps_accuracy?:$outRow?->gps_accuracy,'check_in_selfie_path'=>$this->null($in?->check_in_selfie_path?:$in?->selfie_path),'check_out_selfie_path'=>$this->null($outRow?->check_out_selfie_path?:$outRow?->selfie_path),'notes'=>$this->null(collect($group)->pluck('rejection_reason')->filter()->implode('; ')),'legacy_attendance_ids'=>json_encode($group->pluck('id')->values()),'legacy_snapshot'=>json_encode($group->map(fn($x)=>(array)$x)->values(),JSON_UNESCAPED_SLASHES),'created_at'=>$group->min('created_at')?:$now,'updated_at'=>$group->max('updated_at')?:$now];}
        $out['attendance_records']=$new;$this->stat('Rekap absensi',$m->table('attendances')->count(),count($new),$attendance->count()-count($new)-$skip,$skip);

        $leaves=$m->table('attendance_permissions')->get();$existing=$p->table('attendance_leave_requests')->whereNotNull('legacy_permission_id')->pluck('legacy_permission_id')->flip();$new=[];$skip=0;
        foreach($leaves as$r){$e=$employees->get($r->user_id);$s=$school($r->kelas_id);if(!$e||!$s||!$users->has($r->user_id)){$skip++;continue;}if($existing->has($r->id))continue;$new[]=['legacy_permission_id'=>$r->id,'user_id'=>$r->user_id,'employee_id'=>$e->id,'school_id'=>$s->id,'leave_type'=>$r->category,'start_date'=>$r->start_date,'end_date'=>$r->end_date,'reason'=>$this->null($r->reason)??'-','attachment_path'=>$this->null($r->attachment_path),'status'=>$r->status,'review_notes'=>$this->null($r->review_notes),'reviewed_by'=>$users->has($r->reviewer_id)?$r->reviewer_id:null,'reviewed_at'=>$r->reviewed_at,'created_at'=>$r->created_at?:$now,'updated_at'=>$r->updated_at?:$now];}
        $out['attendance_leave_requests']=$new;$this->stat('Izin absensi',$leaves->count(),count($new),$leaves->count()-count($new)-$skip,$skip);

        if ($this->option('only') === 'attendance') {
            return $out;
        }
        if ($this->option('only') === 'activity') {
            $this->migrateActivityOnly($out, $m, $p, $school, $employees);
            return $out;
        }
        if ($this->option('only') === 'correspondence') {
            $this->migrateCorrespondenceOnly($out, $m, $p, $school);
            return $out;
        }
        if ($this->option('only') === 'proposal') {
            $this->migrateProposalOnly($out, $m, $p, $school);
            return $out;
        }
        if ($this->option('only') === 'sipinter') {
            $this->migrateSipinterOnly($out, $m, $p, $school);
            return $out;
        }

        $this->simpleRequests($out,$m,$p,$school,$users,$now);
        $this->recaps($out,$m,$p,$school,$now);
        $this->supporting($out,$m,$p,$school,$employees,$users,$foundation,$now);
        return $out;
    }

    private function simpleRequests(array &$out,$m,$p,$school,$users,$now):void
    {
        $this->migrateCorrespondenceOnly($out, $m, $p, $school);
        $src=$m->table('proposal')->get();$ex=$p->table('proposal_requests')->whereNotNull('legacy_proposal_id')->pluck('legacy_proposal_id')->flip();$new=[];$skip=0;
        foreach($src as$r){$s=$school($r->kelas_id);if(!$s){$skip++;continue;}if($ex->has($r->id))continue;$new[]=['legacy_proposal_id'=>$r->id,'school_id'=>$s->id,'school_name'=>$s->name,'proposal_type'=>$r->jenis_proposal,'request_file_path'=>$r->proposal,'requested_amount'=>$this->money($r->nominal),'bank_name'=>$r->nama_bank?:'-','bank_account_number'=>$r->no_rekening?:'-','bank_account_name'=>$this->null($r->an_rekening),'description'=>$this->null($r->catatan),'process_status'=>$this->status($r->status),'approval_file_path'=>$this->null($r->approve_proposal),'approved_amount'=>$this->money($r->nominal_acc,true),'notes'=>$this->null($r->keterangan_ditolak),'created_at'=>$r->created_at,'updated_at'=>$r->updated_at,'legacy_snapshot'=>json_encode((array)$r,JSON_UNESCAPED_SLASHES)];}
        $out['proposal_requests']=$new;$this->stat('Proposal',$src->count(),count($new),$src->count()-count($new)-$skip,$skip);
    }

    private function migrateCorrespondenceOnly(array &$out, $m, $p, $school): void
    {
        $types = $p->table('correspondence_types')->get();
        $src = $m->table('persuratan')->get();
        $existing = $p->table('correspondence_requests')
            ->whereNotNull('legacy_correspondence_id')
            ->pluck('legacy_correspondence_id')
            ->flip();
        $new = [];
        $skip = 0;

        foreach ($src as $row) {
            $schoolRecord = $school($row->kelas);
            if (! $schoolRecord) {
                $skip++;
                continue;
            }
            if ($existing->has($row->id)) {
                continue;
            }

            $type = $types->first(fn ($item) => str_contains(
                SidikmaMasterData::normalized($item->name),
                SidikmaMasterData::normalized($row->jenis),
            ));

            $new[] = [
                'legacy_correspondence_id' => $row->id,
                'school_id' => $schoolRecord->id,
                'school_name' => $schoolRecord->name,
                'correspondence_type_id' => $type?->id,
                'type_name' => $row->jenis,
                'request_file_path' => $row->persuratan ?: '-',
                'response_file_path' => $this->null($row->surat_acc),
                'process_status' => $this->status($row->status),
                'notes' => $this->null($row->catatan),
                'created_at' => $row->created_at,
                'updated_at' => $row->created_at,
                'legacy_snapshot' => json_encode((array) $row, JSON_UNESCAPED_SLASHES),
            ];
        }

        $out['correspondence_requests'] = $new;
        $this->stat('Persuratan', $src->count(), count($new), $src->count() - count($new) - $skip, $skip);
    }

    private function migrateActivityOnly(array &$out, $m, $p, $school, $employees): void
    {
        $src = $m->table('aktivasi')->get();
        $existing = $p->table('employee_activity_requests')
            ->whereNotNull('legacy_activity_id')
            ->pluck('legacy_activity_id')
            ->flip();
        $new = [];
        $skip = 0;

        foreach ($src as $row) {
            if ($existing->has($row->id)) {
                continue;
            }

            $schoolRecord = $school($row->kelas);
            $employee = $employees->first(fn ($item) => SidikmaMasterData::normalized($item->name) === SidikmaMasterData::normalized($row->nama));
            if (! $schoolRecord && ! $employee) {
                $skip++;
                continue;
            }

            $new[] = [
                'legacy_activity_id' => $row->id,
                'employee_id' => $employee?->id,
                'employee_name' => $this->null($row->nama) ?? 'Tidak diketahui',
                'school_id' => $schoolRecord?->id,
                'school_name' => $schoolRecord?->name ?? $this->null($row->kelas),
                'inactive_date' => ($inactiveDate = $this->null($row->tmt_nonaktif)) ? substr($inactiveDate, 0, 10) : null,
                'request_letter_path' => $this->null($row->s_permohonan) ?? '-',
                'status' => $this->status($row->status),
                'legacy_snapshot' => json_encode((array) $row, JSON_UNESCAPED_SLASHES),
                'created_at' => $row->created_at,
                'updated_at' => $row->created_at,
            ];
        }

        $out['employee_activity_requests'] = $new;
        $this->stat('Aktivasi/nonaktif', $src->count(), count($new), $src->count() - count($new) - $skip, $skip);
    }

    private function migrateProposalOnly(array &$out, $m, $p, $school): void
    {
        $src = $m->table('proposal')->get();
        $existing = $p->table('proposal_requests')->whereNotNull('legacy_proposal_id')->pluck('legacy_proposal_id')->flip();
        $new = [];
        $skip = 0;

        foreach ($src as $row) {
            if ($existing->has($row->id)) {
                continue;
            }
            $schoolRecord = $school($row->kelas_id);
            if (! $schoolRecord) {
                $skip++;
                continue;
            }
            $new[] = [
                'legacy_proposal_id' => $row->id,
                'school_id' => $schoolRecord->id,
                'school_name' => $schoolRecord->name,
                'proposal_type' => $this->null($row->jenis_proposal) ?? 'Proposal bantuan',
                'request_file_path' => $this->null($row->proposal) ?? '-',
                'requested_amount' => $this->money($row->nominal),
                'bank_name' => $this->null($row->nama_bank) ?? '-',
                'bank_account_number' => $this->null($row->no_rekening) ?? '-',
                'bank_account_name' => $this->null($row->an_rekening),
                'description' => $this->null($row->catatan),
                'process_status' => $this->status($row->status),
                'approval_file_path' => $this->null($row->approve_proposal),
                'approved_amount' => $this->money($row->nominal_acc, true),
                'notes' => $this->null($row->keterangan_ditolak),
                'legacy_snapshot' => json_encode((array) $row, JSON_UNESCAPED_SLASHES),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        }

        $out['proposal_requests'] = $new;
        $this->stat('Proposal', $src->count(), count($new), $src->count() - count($new) - $skip, $skip);
    }

    private function recaps(array &$out,$m,$p,$school,$now):void
    {
        $sources=[['data_siswa','madrasah_id','tahun_pelajaran',['k1'=>'kelas1','k2'=>'kelas2','k3'=>'kelas3','k4'=>'kelas4','k5'=>'kelas5','k6'=>'kelas6','k7'=>'kelas7','k8'=>'kelas8','k9'=>'kelas9'],'student_enrollments'],['kesiswaan','kelas_id','thn_pelajaran',['k1'=>'kelas1','k2'=>'kelas2','k3'=>'kelas3','k4'=>'kelas4','k5'=>'kelas5','k6'=>'kelas6','k7'=>'kelas7','k8'=>'kelas8','k9'=>'kelas9'],'student_enrollments']];
        foreach($sources as[$table,$sid,$year,$map,$target]){foreach($m->table($table)->get() as$r){$s=$school($r->{$sid});$yr=$this->year($r->{$year});if(!$s||!$yr)continue;$payload=['school_id'=>$s->id,'academic_year'=>$yr,'created_at'=>$r->created_at??$now,'updated_at'=>$r->updated_at??$now,'legacy_snapshot'=>json_encode(['source'=>$table,'row'=>(array)$r],JSON_UNESCAPED_SLASHES)];foreach($map as$to=>$from)$payload[$to]=max(0,(int)$r->{$from});$payload['total']=array_sum(array_intersect_key($payload,$map));$out[$target][]=$payload;}}
        $out['student_enrollments']=$this->dedupe($out['student_enrollments']??[]);$srcCount=$m->table('data_siswa')->count()+$m->table('kesiswaan')->count();$ex=$p->table('student_enrollments')->get()->keyBy(fn($r)=>$r->school_id.'|'.$r->academic_year);$out['student_enrollments']=array_values(array_filter($out['student_enrollments'],fn($r)=>!$ex->has($r['school_id'].'|'.$r['academic_year'])));$this->stat('Rekap siswa',$srcCount,count($out['student_enrollments']),$srcCount-count($out['student_enrollments']),0);
        foreach([['data_tenaga_pendidik','madrasah_id','tahun_pelajaran'],['tenaga','kelas_id','thn_pelajaran']]as[$table,$sid,$year])foreach($m->table($table)->get()as$r){$s=$school($r->{$sid});$yr=$this->year($r->{$year});if(!$s||!$yr)continue;if($table==='data_tenaga_pendidik'){$vals=[(int)$r->kepala_guru_asn_sertifikasi,(int)$r->kepala_guru_asn_non_sertifikasi,(int)$r->kepala_guru_yayasan_sertifikasi_inpassing,(int)$r->kepala_guru_yayasan_non_sertifikasi];}else{$vals=[(int)$r->jml_pns,0,(int)$r->jml_gtysertifikasiinpassing,(int)$r->jml_gtynonsertifikasi+(int)$r->jml_pty+(int)$r->jml_gtysertifikasinoninpassing];}$out['educator_recaps'][]=['school_id'=>$s->id,'academic_year'=>$yr,'asn_certified'=>$vals[0],'asn_uncertified'=>$vals[1],'foundation_certified_inpassing'=>$vals[2],'foundation_uncertified'=>$vals[3],'total'=>array_sum($vals),'legacy_snapshot'=>json_encode(['source'=>$table,'row'=>(array)$r],JSON_UNESCAPED_SLASHES),'created_at'=>$r->created_at??$now,'updated_at'=>$r->updated_at??$now];}
        $out['educator_recaps']=$this->dedupe($out['educator_recaps']??[]);$srcCount=$m->table('data_tenaga_pendidik')->count()+$m->table('tenaga')->count();$ex=$p->table('educator_recaps')->get()->keyBy(fn($r)=>$r->school_id.'|'.$r->academic_year);$out['educator_recaps']=array_values(array_filter($out['educator_recaps'],fn($r)=>!$ex->has($r['school_id'].'|'.$r['academic_year'])));$this->stat('Rekap pendidik',$srcCount,count($out['educator_recaps']),$srcCount-count($out['educator_recaps']),0);
    }

    private function migrateSipinterOnly(array &$out, $m, $p, $school): void
    {
        $src = $m->table('updatesipinter')->get();
        $existing = $p->table('sipinter_updates')->whereNotNull('legacy_update_id')->pluck('legacy_update_id')->flip();
        $new = [];
        $skip = 0;
        foreach ($src as $row) {
            $schoolRecord = $school($row->kelas);
            if (! $schoolRecord) { $skip++; continue; }
            if ($existing->has($row->id) || $p->table('sipinter_updates')->where('school_id', $schoolRecord->id)->exists()) continue;
            $new[] = [
                'legacy_update_id' => $row->id,
                'school_id' => $schoolRecord->id,
                'request_file_path' => $row->updatesipinter ?: '-',
                'asset_file_path' => $this->null($row->akta),
                'recommendation_file_path' => $this->null($row->pcnu) ?? $this->null($row->pwnu),
                'npsn' => SidikmaSchoolData::validNpsn($row->npsn),
                'school_address' => $this->null($row->alamat),
                'land_ownership' => $this->null($row->tanah),
                'land_status' => $this->null($row->status_tanah),
                'management_authority' => $this->null($row->pengelolaakta),
                'uses_notarial_deed' => SidikmaSchoolData::yesNo($row->akta),
                'legacy_snapshot' => json_encode((array) $row, JSON_UNESCAPED_SLASHES),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        }
        $out['sipinter_updates'] = $new;
        $this->stat('Update SIPINTER', $src->count(), count($new), $src->count() - count($new) - $skip, $skip);
    }

    private function supporting(array &$out,$m,$p,$school,$employees,$users,$foundation,$now):void
    {
        $src=$m->table('updatesipinter')->get();$ex=$p->table('sipinter_updates')->whereNotNull('legacy_update_id')->pluck('legacy_update_id')->flip();$new=[];$skip=0;foreach($src as$r){$s=$school($r->kelas);if(!$s){$skip++;continue;}if($ex->has($r->id)||$p->table('sipinter_updates')->where('school_id',$s->id)->exists())continue;$new[]=['legacy_update_id'=>$r->id,'school_id'=>$s->id,'request_file_path'=>$r->updatesipinter?:'-','asset_file_path'=>$this->null($r->akta),'recommendation_file_path'=>$this->null($r->pcnu)??$this->null($r->pwnu),'npsn'=>SidikmaSchoolData::validNpsn($r->npsn),'school_address'=>$this->null($r->alamat),'land_ownership'=>$this->null($r->tanah),'land_status'=>$this->null($r->status_tanah),'management_authority'=>$this->null($r->pengelolaakta),'uses_notarial_deed'=>SidikmaSchoolData::yesNo($r->akta),'legacy_snapshot'=>json_encode((array)$r,JSON_UNESCAPED_SLASHES),'created_at'=>$r->created_at,'updated_at'=>$r->updated_at];}$out['sipinter_updates']=$new;$this->stat('Update SIPINTER',$src->count(),count($new),$src->count()-count($new)-$skip,$skip);
        $src=$m->table('aktivasi')->get();$ex=$p->table('employee_activity_requests')->whereNotNull('legacy_activity_id')->pluck('legacy_activity_id')->flip();$new=[];$skip=0;foreach($src as$r){$s=$school($r->kelas);$e=$employees->first(fn($e)=>SidikmaMasterData::normalized($e->name)===SidikmaMasterData::normalized($r->nama));if($ex->has($r->id))continue;$new[]=['legacy_activity_id'=>$r->id,'employee_id'=>$e?->id,'employee_name'=>$r->nama,'school_id'=>$s?->id,'school_name'=>$s?->name??$this->null($r->kelas),'inactive_date'=>$r->tmt_nonaktif,'request_letter_path'=>$r->s_permohonan?:'-','status'=>$this->status($r->status),'legacy_snapshot'=>json_encode((array)$r,JSON_UNESCAPED_SLASHES),'created_at'=>$r->created_at,'updated_at'=>$r->created_at];}$out['employee_activity_requests']=$new;$this->stat('Aktivasi/nonaktif',$src->count(),count($new),$src->count()-count($new)-$skip,$skip);
        $src=$m->table('stok_batik')->get();$targetProducts=$p->table('batik_products')->get();$ex=$targetProducts->whereNotNull('legacy_product_id')->keyBy('legacy_product_id');$byName=$targetProducts->keyBy(fn($x)=>SidikmaMasterData::normalized($x->name));$new=[];$updates=[];foreach($src as$r){if($ex->has($r->id))continue;$payload=['legacy_product_id'=>$r->id,'foundation_id'=>$foundation->id,'name'=>trim($r->produk),'audience'=>'umum','stock'=>$r->stok,'price'=>$r->harga,'size_label'=>'unit','sort_order'=>$r->id,'is_active'=>true,'legacy_snapshot'=>json_encode((array)$r,JSON_UNESCAPED_SLASHES),'updated_at'=>$r->updated_at?:$r->created_at];$same=$byName->get(SidikmaMasterData::normalized($r->produk));if($same)$updates[]=['id'=>$same->id,'payload'=>$payload];else$new[]=$payload+['created_at'=>$r->created_at];}$out['batik_products']=$new;$out['_batik_product_updates']=$updates;$this->stat('Produk batik',$src->count(),count($new),$src->count()-count($new),0);
        $src=$m->table('batik_maarif')->get();$ex=$p->table('batik_orders')->whereNotNull('legacy_order_id')->pluck('legacy_order_id')->flip();$products=$p->table('batik_products')->get()->keyBy(fn($x)=>SidikmaMasterData::normalized($x->name));$fallback=$products->first();$new=[];$skip=0;foreach($src as$r){$s=$school($r->asal_sekolah);if(!$s||!$fallback){$skip++;continue;}if($ex->has($r->id))continue;$qty=(int)$r->siswa+(int)$r->guru_2m+(int)$r->guru_25m;$new[]=['legacy_order_id'=>$r->id,'foundation_id'=>$foundation->id,'product_id'=>$fallback->id,'school_id'=>$s->id,'ordered_at'=>$r->created_at?:$now,'quantity'=>$qty,'total_amount'=>$r->total_tagihan,'status'=>'collected','recipient_name'=>$this->null($r->penerima),'legacy_snapshot'=>json_encode((array)$r,JSON_UNESCAPED_SLASHES),'created_at'=>$r->created_at?:$now,'updated_at'=>$r->updated_at?:$now];}$out['batik_orders']=$new;$this->stat('Pesanan batik',$src->count(),count($new),$src->count()-count($new)-$skip,$skip);
        foreach([['agenda_kesekretariatans','secretariat_agendas','legacy_agenda_id'],['modul','learning_modules','legacy_module_id']]as[$table,$target,$key]){$src=$m->table($table)->get();$ex=$p->table($target)->whereNotNull($key)->pluck($key)->flip();$new=[];foreach($src as$r){if($ex->has($r->id))continue;$new[]=$table==='modul'?[$key=>$r->id,'foundation_id'=>$foundation->id,'class_name'=>$r->kelas,'module_type'=>$r->jenis_modul,'semester'=>(int)$r->semester,'subject'=>$r->mapel,'chapter'=>$r->bab,'file_path'=>$r->file_path,'original_name'=>basename(str_replace('\\','/',$r->file_path)),'uploaded_at'=>$r->created_at,'legacy_snapshot'=>json_encode((array)$r,JSON_UNESCAPED_SLASHES),'created_at'=>$r->created_at,'updated_at'=>$r->created_at]:[$key=>$r->id,'foundation_id'=>$foundation->id,'activity'=>$r->kegiatan,'implementation_date'=>$r->tanggal_pelaksanaan,'officer'=>$r->petugas,'status'=>$r->keterangan?:'planned','notes'=>$this->null($r->catatan),'legacy_snapshot'=>json_encode((array)$r,JSON_UNESCAPED_SLASHES),'created_at'=>$r->created_at,'updated_at'=>$r->updated_at];}$out[$target]=$new;$this->stat($table==='modul'?'Modul belajar':'Agenda sekretariat',$src->count(),count($new),$src->count()-count($new),0);}
    }

    private function insert(array $rows, string $targetConnection):void { foreach($rows as$table=>$items){if($table==='_batik_product_updates'){foreach($items as$item)DB::connection($targetConnection)->table('batik_products')->where('id',$item['id'])->update(array_map(fn($v)=>is_string($v)&&str_starts_with($v,'0000-00-00')?null:$v,$item['payload']));continue;}foreach(array_chunk($items,100)as$chunk) if($chunk) DB::connection($targetConnection)->table($table)->insert(array_map(fn($row)=>array_map(fn($value)=>is_string($value)&&str_starts_with($value,'0000-00-00')?null:$value,$row),$chunk));} }
    private function stat($name,$source,$new,$existing,$skip):void {$this->counts[$name]=[$source,$new,max(0,$existing),$skip];}
    private function null($v):?string {$v=trim((string)$v);return $v===''||$v==='-'?null:$v;}
    private function status($v):string {$v=SidikmaMasterData::normalized((string)$v);return match(true){str_contains($v,'tolak')||str_contains($v,'tidak disetujui')=>'rejected',str_contains($v,'setuju')||str_contains($v,'approve')||str_contains($v,'acc')||str_contains($v,'selesai')=>'approved',default=>'submitted'};}
    private function money($v,$nullable=false):?float {$s=preg_replace('/[^0-9-]/','',(string)$v);return $s===''?($nullable?null:0):(float)$s;}
    private function year($v):?string {if(preg_match('/(20\d{2})\D+(20\d{2})/',(string)$v,$m))return $m[1].'/'.$m[2];return null;}
    private function dedupe(array $rows):array {$out=[];foreach($rows as$r){$k=$r['school_id'].'|'.$r['academic_year'];if(!isset($out[$k])||array_sum(array_filter($r,'is_int'))>array_sum(array_filter($out[$k],'is_int')))$out[$k]=$r;}return array_values($out);}
}
