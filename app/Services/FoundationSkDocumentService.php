<?php
namespace App\Services;
use App\Models\FoundationSkDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class FoundationSkDocumentService
{
 public const DISK='documents';
 public function store(User $user,int $year,int $templateId,UploadedFile $file,User $actor,string $source='single',?string $matchedBy=null, string $directory='sk_yayasan'): FoundationSkDocument
 { return DB::transaction(function() use($user,$year,$templateId,$file,$actor,$source,$matchedBy,$directory){$doc=FoundationSkDocument::query()->where(['user_id'=>$user->id,'tahun_sk'=>$year,'sk_template_id'=>$templateId])->lockForUpdate()->first();$old=$doc?->file_path;$name=Str::slug($user->name).'-'.$year.'-'.Str::random(12).'.'.$file->extension();$path="{$directory}/{$year}/{$name}";$stored=Storage::disk(self::DISK)->putFileAs("{$directory}/{$year}",$file,$name);if(!$stored)throw new \RuntimeException('Dokumen gagal disimpan.');$data=['user_id'=>$user->id,'sk_template_id'=>$templateId,'tahun_sk'=>$year,'original_filename'=>$file->getClientOriginalName(),'stored_filename'=>$name,'file_path'=>$path,'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),'source_type'=>$source,'matched_by'=>$matchedBy,'uploaded_by'=>$actor->id];try{if($doc){$doc->update($data);if($old && $old!==$path)Storage::disk(self::DISK)->delete($old);return $doc->fresh();}return FoundationSkDocument::create($data);}catch(\Throwable $e){Storage::disk(self::DISK)->delete($path);throw $e;}}); }
 public function normalize(string $value): string { $value=Str::ascii(pathinfo($value,PATHINFO_FILENAME));return strtolower((string)preg_replace('/[^a-z0-9]+/i','',$value)); }
}
