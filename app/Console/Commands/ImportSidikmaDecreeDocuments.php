<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class ImportSidikmaDecreeDocuments extends Command
{
    protected $signature='sidikma:import-decree-documents {--dry-run} {--force}';
    protected $description='Impor dokumen SK Yayasan SIDIKMA ke penyimpanan privat';

    public function handle():int
    {
        $root=storage_path('app/private/sidikma-legacy');if(!is_dir($root)){$this->error('Arsip legacy tidak ditemukan.');return self::FAILURE;}$index=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root))as$f)if($f->isFile())$index[mb_strtolower($f->getFilename())][]=$f->getPathname();
        $p=DB::connection('pgsql');$employees=$p->table('employees')->whereNull('deleted_at')->whereNotNull('user_id')->pluck('id','user_id');$users=$p->table('users')->pluck('id')->flip();$existing=$p->table('documents')->where('document_type','sk')->whereNotNull('checksum')->get()->keyBy(fn($d)=>$d->owner_type.'|'.$d->owner_id.'|'.$d->checksum);$rows=DB::connection('mysql_sidikma')->table('sk_yayasan_documents')->orderBy('id')->get();$valid=[];$missing=[];$ambiguous=[];$orphan=[];$duplicates=0;
        foreach($rows as$r){$targetUser=(int)$r->user_id===1833?11:(int)$r->user_id;$employeeId=$employees->get($targetUser);$ownerType=$employeeId?Employee::class:User::class;$ownerId=$employeeId?:($users->has($targetUser)?$targetUser:null);if(!$ownerId){$orphan[]="$r->id|user:$r->user_id";continue;}$candidates=array_values(array_unique(array_filter(array_map(fn($v)=>basename(str_replace('\\','/',(string)$v)),[$r->original_filename,$r->stored_filename,$r->file_path]))));$matches=[];$base=$candidates[0]??'';foreach($candidates as$candidate){$candidateMatches=$index[mb_strtolower($candidate)]??[];if(count($candidateMatches)===1){$matches=$candidateMatches;$base=$candidate;break;}if(count($candidateMatches)>1){$exact=array_values(array_filter($candidateMatches,fn($path)=>basename($path)===$candidate));if(count($exact)===1){$matches=$exact;$base=$candidate;break;}if(!$matches){$matches=$candidateMatches;$base=$candidate;}}}if(count($matches)===0){$missing[]="$r->id|".implode(' OR ',$candidates);continue;}if(count($matches)>1){$ambiguous[]="$r->id|$base|".count($matches);continue;}$data=file_get_contents($matches[0]);if($data===false){$missing[]="$r->id|$base";continue;}$checksum=hash('sha256',$data);if($existing->has($ownerType.'|'.$ownerId.'|'.$checksum)){$duplicates++;continue;}$ext=strtolower(pathinfo($base,PATHINFO_EXTENSION))?:'pdf';$dir=$employeeId?'employees/'.$employeeId:'users/'.$ownerId;$path=$dir.'/legacy-sk-yayasan-'.$r->id.'.'.$ext;$valid[]=compact('ownerType','ownerId','path','base','checksum','data')+['mime'=>$r->mime_type?:'application/pdf','created'=>$r->created_at?:now()];}
        $this->table(['Status','Jumlah'],[['Sumber',$rows->count()],['Siap diimpor',count($valid)],['Duplikat checksum',$duplicates],['Tidak ditemukan',count($missing)],['Nama ambigu',count($ambiguous)],['User orphan',count($orphan)]]);$this->report('sidikma-decree-documents-missing.txt',$missing);$this->report('sidikma-decree-documents-ambiguous.txt',$ambiguous);$this->report('sidikma-decree-documents-orphan.txt',$orphan);if($this->option('dry-run'))return self::SUCCESS;if(!$this->option('force')&&!$this->confirm('Impor dokumen SK yang belum ada?'))return self::SUCCESS;
        try{$p->transaction(function()use($p,$valid){foreach($valid as$r){if(!Storage::disk('documents')->put($r['path'],$r['data']))throw new \RuntimeException('Gagal menyalin '.$r['base']);$p->table('documents')->insert(['document_type'=>'sk','owner_type'=>$r['ownerType'],'owner_id'=>$r['ownerId'],'disk'=>'documents','path'=>$r['path'],'original_name'=>$r['base'],'mime_type'=>$r['mime'],'size'=>strlen($r['data']),'checksum'=>$r['checksum'],'status'=>'active','created_at'=>$r['created'],'updated_at'=>now()]);}});}catch(Throwable$e){report($e);$this->error($e->getMessage());return self::FAILURE;}$this->info('IMPOR SK YAYASAN SELESAI — '.count($valid).' dokumen baru.');return self::SUCCESS;
    }
    private function report(string$name,array$rows):void{file_put_contents(storage_path('app/private/'.$name),implode(PHP_EOL,$rows));}
}
