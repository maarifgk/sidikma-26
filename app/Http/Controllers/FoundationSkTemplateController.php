<?php
namespace App\Http\Controllers;
use App\Models\{FoundationSkTemplate,User};
use App\Services\{FoundationSkNumberService,FoundationSkPdfService};
use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use App\Services\FoundationSkDocumentService;

class FoundationSkTemplateController extends Controller {
 private function manage():void{abort_unless(auth()->user()?->isAdminInduk()||auth()->user()?->can('sk-yayasan.manage'),403);}
 private function user(Request $r):User{$u=User::query()->with('employee.school')->findOrFail($r->integer('user_id'));$a=auth()->user();abort_unless($a->isAdminInduk()||$a->accessibleSchoolIds()->contains($u->employee?->school_id),403);return $u;}
 public function preview(Request $r,FoundationSkTemplate $template){Gate::authorize('view',$template);$u=$r->filled('user_id')?$this->user($r):null;$rendered=app(FoundationSkPdfService::class)->renderHtml($template,$u,$r->only(['periode','tahun','nomor_sk','teks_nomor_sk']));$html=preg_match('/<body[^>]*>(.*)<\/body>/is',$rendered,$m)?$m[1]:$rendered;return response()->view('foundation-sk.preview',['template'=>$template,'html'=>$html]);}
 public function generate(Request $r,FoundationSkTemplate $template){$this->manage();$r->validate(['user_id'=>'required|integer','periode'=>'required|string|max:20','mode'=>'nullable|in:download_only,save_document']);$u=$this->user($r);$pdf=app(FoundationSkPdfService::class)->generateOne($template,$u,$r->string('periode')->toString(),app(FoundationSkNumberService::class),$r->only('teks_nomor_sk'));if($r->input('mode','download_only')==='save_document'){$tmp=tempnam(sys_get_temp_dir(),'sk-pdf-');file_put_contents($tmp,$pdf);try{app(FoundationSkDocumentService::class)->store($u,(int)preg_replace('/[^0-9].*/','',$r->string('periode')->toString()),$template->id,new UploadedFile($tmp,'generated-'.$u->id.'.pdf','application/pdf',null,true),auth()->user(),'generated','generated','sk_yayasan/generated');}finally{@unlink($tmp);}}return response($pdf,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="sk-'.$u->id.'.pdf"']);}
 public function batch(Request $r,FoundationSkTemplate $template){$this->manage();$r->validate(['user_ids'=>'required|array|min:1','user_ids.*'=>'integer','periode'=>'required|string|max:20','mode'=>'nullable|in:download_only,save_document']);$users=User::query()->with('employee.school')->whereIn('id',$r->input('user_ids'))->get()->all();foreach($users as $u){abort_unless(auth()->user()->isAdminInduk()||auth()->user()->accessibleSchoolIds()->contains($u->employee?->school_id),403);}$files=app(FoundationSkPdfService::class)->generateBatch($template,$users,$r->string('periode')->toString(),app(FoundationSkNumberService::class));if($r->input('mode')==='save_document'){DB::transaction(function()use($files,$users,$template,$r){foreach($files as $i=>$pdf){$tmp=tempnam(sys_get_temp_dir(),'sk-pdf-');file_put_contents($tmp,$pdf);try{app(FoundationSkDocumentService::class)->store($users[$i],(int)preg_replace('/[^0-9].*/','',$r->string('periode')->toString()),$template->id,new UploadedFile($tmp,'generated-'.$users[$i]->id.'.pdf','application/pdf',null,true),auth()->user(),'generated','generated','sk_yayasan/generated');}finally{@unlink($tmp);}}});}$tmp=tempnam(sys_get_temp_dir(),'sk-');$zip=new ZipArchive();abort_unless($zip->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)===true,500);foreach($files as $i=>$pdf)$zip->addFromString('sk-'.$users[$i]->id.'.pdf',$pdf);$zip->close();return response()->download($tmp,'sk-batch.zip')->deleteFileAfterSend(true);}
}
