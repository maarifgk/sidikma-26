<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class ImportSidikmaFiles extends Command
{
    protected $signature = 'sidikma:import-files {--dry-run} {--force}';
    protected $description = 'Cocokkan dan salin berkas SIDIKMA dari arsip privat ke disk modul';

    public function handle(): int
    {
        $root = storage_path('app/private/sidikma-legacy');
        if (! is_dir($root)) { $this->error('Folder arsip privat tidak ditemukan.'); return self::FAILURE; }
        $index = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if (! $file->isFile()) continue;
            $index[$this->key($file->getFilename())][] = $file->getPathname();
        }
        $maps = [
            ['correspondence_requests', 'legacy_correspondence_id', 'correspondence-requests', ['request_file_path', 'response_file_path']],
            ['proposal_requests', 'legacy_proposal_id', 'proposal-requests', ['request_file_path', 'approval_file_path']],
            ['sipinter_updates', 'legacy_update_id', 'sipinter-updates', ['request_file_path', 'asset_file_path', 'recommendation_file_path']],
            ['employee_activity_requests', 'legacy_activity_id', 'employee-activities', ['request_letter_path']],
            ['employee_mutations', 'legacy_mutation_id', 'employee-mutations', ['request_letter_path']],
            ['learning_modules', 'legacy_module_id', 'learning-modules', ['file_path']],
            ['attendance_leave_requests', 'legacy_permission_id', 'attendance', ['attachment_path']],
            ['attendance_records', 'id', 'attendance', ['check_in_selfie_path', 'check_out_selfie_path']],
        ];
        $found=[]; $missing=[]; $ambiguous=[]; $installed=0;
        foreach ($maps as [$table,$legacy,$disk,$columns]) foreach (DB::connection('pgsql')->table($table)->get() as $row) foreach ($columns as $column) {
            $old=trim((string)($row->{$column}??'')); if($old===''||$old==='-')continue;
            if (Storage::disk($disk)->exists($old)) { $installed++; continue; }
            $name=basename(str_replace('\\','/',$old)); $matches=$index[$this->key($name)]??[];
            if(count($matches)===0){$missing[]="$table#$row->id.$column|$old";continue;}
            if(count($matches)>1){$exact=array_values(array_filter($matches,fn($p)=>basename($p)===$name));if(count($exact)===1)$matches=$exact;else{$ambiguous[]="$table#$row->id.$column|$old|".count($matches);continue;}}
            $safe=preg_replace('/[^A-Za-z0-9._ -]/u','_',basename($matches[0]));
            $path='legacy/'.$row->id.'/'.$safe;
            $found[]=compact('table','column','disk','path','old')+['id'=>$row->id,'source'=>$matches[0]];
        }
        $this->table(['Status','Jumlah'],[['Sudah terpasang',$installed],['Cocok unik',count($found)],['Tidak ditemukan',count($missing)],['Ambigu',count($ambiguous)]]);
        if($this->option('dry-run')){$this->writeReports($missing,$ambiguous);$this->info('DRY RUN SELESAI — file dan database tidak diubah.');return self::SUCCESS;}
        if(!$this->option('force')&&!$this->confirm('Salin berkas cocok dan perbarui path database?'))return self::SUCCESS;
        try{DB::connection('pgsql')->transaction(function()use($found){foreach($found as$f){$data=file_get_contents($f['source']);if($data===false||!Storage::disk($f['disk'])->put($f['path'],$data))throw new \RuntimeException('Gagal menyalin '.$f['source']);DB::connection('pgsql')->table($f['table'])->where('id',$f['id'])->update([$f['column']=>$f['path']]);}});}catch(Throwable$e){report($e);$this->error($e->getMessage());return self::FAILURE;}
        $this->writeReports($missing,$ambiguous);$this->info('IMPOR BERKAS SELESAI — '.count($found).' referensi dipasang.');return self::SUCCESS;
    }

    private function key(string $name): string { return mb_strtolower(trim($name)); }
    private function writeReports(array $missing,array $ambiguous):void { file_put_contents(storage_path('app/private/sidikma-files-missing.txt'),implode(PHP_EOL,$missing));file_put_contents(storage_path('app/private/sidikma-files-ambiguous.txt'),implode(PHP_EOL,$ambiguous)); }
}
