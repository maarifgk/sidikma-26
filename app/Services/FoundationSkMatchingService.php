<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
class FoundationSkMatchingService
{
 public function match(UploadedFile|string $file, User $actor, ?int $schoolId=null): array
 { $name=$file instanceof UploadedFile ? $file->getClientOriginalName() : $file; $needle=$this->normalize($name); $query=Employee::query()->with('user')->where('is_active',true)->whereNotNull('user_id'); if(! $actor->isAdminInduk())$query->whereIn('school_id',$actor->accessibleSchoolIds()); if($schoolId!==null)$query->where('school_id',$schoolId); $scores=$query->get()->map(fn(Employee $e)=>['employee'=>$e,'score'=>$this->score($needle,$e)])->filter(fn(array $x)=>$x['score']>0)->sortByDesc('score')->values(); if($scores->isEmpty())return ['status'=>'unmatched','candidates'=>[]];$best=$scores->first()['score'];$best=$scores->where('score',$best);if($best->count()>1)return ['status'=>'ambiguous','candidates'=>$best->pluck('employee')->values()->all()];return ['status'=>'matched','employee'=>$best->first()['employee'],'matched_by'=>$this->matchedBy($needle,$best->first()['employee']),'score'=>$best->first()['score']]; }
 public function normalize(string $value): string { $value=pathinfo($value,PATHINFO_FILENAME);$value=mb_strtolower((string)\Illuminate\Support\Str::ascii($value));$value=preg_replace('/\b(sk|surat|keputusan)\b/u',' ',$value);return (string)preg_replace('/[^a-z0-9]+/','',$value); }
 private function score(string $needle,Employee $e): int { foreach([$e->employee_code,$e->nik] as $value)if(filled($value)&&$needle===$this->normalize((string)$value))return 1000;foreach([$e->nuptk,$e->nip] as $value)if(filled($value)&&$needle===$this->normalize((string)$value))return 900;$full=$this->normalize($e->name);if($needle===$full)return 800;$parts=collect(preg_split('/\s+/',$e->name))->map(fn($v)=>$this->normalize($v))->filter(fn($v)=>strlen($v)>=3)->values();$tokens=$parts->filter(fn($p)=>str_contains($needle,$p));if($tokens->count()>=2)return 500;if($tokens->count()===1&&strlen($tokens->first())>=4)return 200;return 0; }
 private function matchedBy(string $needle,Employee $e): string { foreach(['employee_code'=>'nis','nuptk'=>'nuptk','nip'=>'nip'] as $field=>$label)if(filled($e->{$field})&&$needle===$this->normalize((string)$e->{$field}))return $label;return $needle===$this->normalize($e->name)?'nama_lengkap':'nama'; }
}
