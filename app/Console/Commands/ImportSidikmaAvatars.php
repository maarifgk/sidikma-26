<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use finfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class ImportSidikmaAvatars extends Command
{
    protected $signature = 'sidikma:import-avatars {--dry-run} {--force}';
    protected $description = 'Impor foto profil SIDIKMA ke disk publik lokal';

    public function handle(): int
    {
        $root=storage_path('app/private/sidikma-legacy');
        if(!is_dir($root)){$this->error('Folder arsip legacy tidak ditemukan.');return self::FAILURE;}
        $index=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root))as$f){if($f->isFile())$index[mb_strtolower($f->getFilename())][]=$f->getPathname();}
        $legacy=DB::connection('mysql_sidikma')->table('users')->whereNotNull('image')->where('image','<>','')->select('id','nama_lengkap','image')->get();
        $targetUsers=DB::connection('pgsql')->table('users')->pluck('id')->flip();$employees=DB::connection('pgsql')->table('employees')->whereNotNull('user_id')->pluck('id','user_id');
        $valid=[];$missing=[];$ambiguous=[];$invalid=[];$mime=new finfo(FILEINFO_MIME_TYPE);
        foreach($legacy as$r){if(!$targetUsers->has((int)$r->id))continue;$name=basename(str_replace('\\','/',$r->image));$matches=$index[mb_strtolower($name)]??[];if(count($matches)===0){$missing[]="$r->id|$r->nama_lengkap|$name";continue;}if(count($matches)>1){$exact=array_values(array_filter($matches,fn($p)=>basename($p)===$name));if(count($exact)===1)$matches=$exact;else{$ambiguous[]="$r->id|$r->nama_lengkap|$name|".count($matches);continue;}}$type=$mime->file($matches[0]);$ext=match($type){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>null};if(!$ext){$invalid[]="$r->id|$r->nama_lengkap|$name|$type";continue;}$path='employee-avatars/legacy-'.$r->id.'.'.$ext;$valid[]=['user_id'=>(int)$r->id,'employee_id'=>$employees->get((int)$r->id),'source'=>$matches[0],'path'=>$path];}
        $this->table(['Status','Jumlah'],[['Foto valid',count($valid)],['Tidak ditemukan',count($missing)],['Nama ambigu',count($ambiguous)],['Bukan gambar valid',count($invalid)]]);
        $this->reports($missing,$ambiguous,$invalid);if($this->option('dry-run')){$this->info('DRY RUN SELESAI — file dan database tidak diubah.');return self::SUCCESS;}if(!$this->option('force')&&!$this->confirm('Pasang foto profil yang valid?'))return self::SUCCESS;
        try{DB::connection('pgsql')->transaction(function()use($valid){foreach($valid as$r){$data=file_get_contents($r['source']);if($data===false||!Storage::disk('public')->put($r['path'],$data))throw new \RuntimeException('Gagal menyalin '.$r['source']);DB::connection('pgsql')->table('users')->where('id',$r['user_id'])->update(['avatar_path'=>$r['path'],'updated_at'=>now()]);if($r['employee_id'])DB::connection('pgsql')->table('employees')->where('id',$r['employee_id'])->update(['avatar_path'=>$r['path'],'updated_at'=>now()]);}});}catch(Throwable$e){report($e);$this->error($e->getMessage());return self::FAILURE;}
        $this->info('IMPOR FOTO SELESAI — '.count($valid).' profil diperbarui.');return self::SUCCESS;
    }

    private function reports(array$m,array$a,array$i):void{file_put_contents(storage_path('app/private/sidikma-avatar-missing.txt'),implode(PHP_EOL,$m));file_put_contents(storage_path('app/private/sidikma-avatar-ambiguous.txt'),implode(PHP_EOL,$a));file_put_contents(storage_path('app/private/sidikma-avatar-invalid.txt'),implode(PHP_EOL,$i));}
}
