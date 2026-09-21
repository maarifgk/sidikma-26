<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use finfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class ImportSidikmaEmployeeDocuments extends Command
{
    protected $signature='sidikma:import-employee-documents {--dry-run} {--force}';
    protected $description='Impor SK dan ijazah legacy yang dapat dipasangkan pasti ke pegawai';
    public function handle():int
    {
        $root=storage_path('app/private/sidikma-legacy');if(!is_dir($root))return self::FAILURE;$index=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root))as$f)if($f->isFile())$index[mb_strtolower($f->getFilename())][]=$f->getPathname();
        $employees=DB::connection('pgsql')->table('employees')->whereNotNull('user_id')->pluck('id','user_id');$rows=DB::connection('mysql_sidikma')->table('users')->select('id','nama_lengkap','skbfr2025','sk01_2025')->get();$valid=[];$missing=[];$ambiguous=[];$mime=new finfo(FILEINFO_MIME_TYPE);
        foreach($rows as$r){$employeeId=$employees->get((int)$r->id);if(!$employeeId)continue;foreach(['skbfr2025','sk01_2025']as$column){$name=trim((string)$r->{$column});if($name===''||$name==='-')continue;$base=basename(str_replace('\\','/',$name));$matches=$index[mb_strtolower($base)]??[];if(count($matches)===0){$missing[]="$r->id|$column|$base";continue;}if(count($matches)>1){$exact=array_values(array_filter($matches,fn($p)=>basename($p)===$base));if(count($exact)===1)$matches=$exact;else{$ambiguous[]="$r->id|$column|$base|".count($matches);continue;}}$source=$matches[0];$type=$mime->file($source)?:'application/octet-stream';$ext=strtolower(pathinfo($source,PATHINFO_EXTENSION))?:'bin';$path='employees/'.$employeeId.'/legacy-'.$column.'-'.$r->id.'.'.$ext;$valid[]=compact('employeeId','source','path','type','base');}}
        $this->table(['Status','Jumlah'],[['Cocok unik',count($valid)],['Tidak ditemukan',count($missing)],['Ambigu',count($ambiguous)]]);file_put_contents(storage_path('app/private/sidikma-employee-documents-missing.txt'),implode(PHP_EOL,$missing));file_put_contents(storage_path('app/private/sidikma-employee-documents-ambiguous.txt'),implode(PHP_EOL,$ambiguous));if($this->option('dry-run'))return self::SUCCESS;if(!$this->option('force')&&!$this->confirm('Impor dokumen pegawai?'))return self::SUCCESS;
        try{DB::connection('pgsql')->transaction(function()use($valid){foreach($valid as$r){$data=file_get_contents($r['source']);if($data===false||!Storage::disk('documents')->put($r['path'],$data))throw new \RuntimeException('Gagal menyalin '.$r['base']);$checksum=hash('sha256',$data);DB::connection('pgsql')->table('documents')->updateOrInsert(['path'=>$r['path']],['document_type'=>'sk','owner_type'=>Employee::class,'owner_id'=>$r['employeeId'],'disk'=>'documents','original_name'=>$r['base'],'mime_type'=>$r['type'],'size'=>strlen($data),'checksum'=>$checksum,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);}});}catch(Throwable$e){report($e);$this->error($e->getMessage());return self::FAILURE;}$this->info('IMPOR DOKUMEN SELESAI — '.count($valid).' dokumen dipasang.');return self::SUCCESS;
    }
}
