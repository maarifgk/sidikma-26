<?php
namespace App\Services;
use App\Models\FoundationSkNumberSetting;
use Illuminate\Support\Facades\DB;

class FoundationSkNumberService {
 public function nextFor(string $periode, callable $operation, array $values=[]): mixed {
  return DB::transaction(function() use($periode,$operation,$values){
   $number=$this->next($periode,$values);
   return $operation($number);
  });
 }
 public function next(string $periode, array $values=[]): string {
  return DB::transaction(function() use($periode,$values){
   $s=FoundationSkNumberSetting::query()->where('periode',$periode)->where('is_active',true)->lockForUpdate()->firstOrFail();
   $raw=max((int)$s->nomor_awal,(int)$s->nomor_berikutnya); $number=str_pad((string)$raw,(int)$s->digit_nomor,'0',STR_PAD_LEFT);
   $map=['nomor_urut'=>$number,'nomor_urut_raw'=>(string)$raw,'teks_nomor_sk'=>(string)($s->nomor_text??''),'periode'=>$periode,'periode_upper'=>strtoupper($periode),'tahun'=>preg_replace('/[^0-9].*/','',$periode),'bulan_romawi'=>$this->roman((int)now()->month)]+$values;
   $result=preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i',fn($m)=>(string)($map[$m[1]]??$m[0]),(string)$s->nomor_pattern);
   $s->update(['nomor_berikutnya'=>$raw+1]); return (string)$result;
  });
 }
 private function roman(int $n):string { $out=''; foreach([1000=>'M',900=>'CM',500=>'D',400=>'CD',100=>'C',90=>'XC',50=>'L',40=>'XL',10=>'X',9=>'IX',5=>'V',4=>'IV',1=>'I'] as $v=>$r){while($n>=$v){$out.=$r;$n-=$v;}} return $out; }
}
