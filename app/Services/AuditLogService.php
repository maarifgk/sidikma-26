<?php
namespace App\Services;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
class AuditLogService
{
 public function record(string $action, Model|string $entity, array $new = [], array $old = [], array $context = []): AuditLog
 {
  $model = is_object($entity) ? $entity : null; $userAgent = request()->userAgent();
  return AuditLog::query()->create(['actor_user_id'=>$context['actor_user_id'] ?? auth()->id(),'target_user_id'=>$context['target_user_id'] ?? ($model?->user_id),'employee_id'=>$context['employee_id'] ?? ($model?->employee_id),'school_id'=>$context['school_id'] ?? ($model?->school_id),'action'=>$action,'entity_type'=>is_string($entity)?$entity:$entity::class,'entity_id'=>$model?->getKey(),'old_values'=>$this->sanitize($old) ?: null,'new_values'=>$this->sanitize($new) ?: null,'ip_address'=>filter_var(request()->ip(), FILTER_VALIDATE_IP) ?: null,'user_agent'=>$userAgent ? mb_substr(preg_replace('/[^\x20-\x7E]/','',$userAgent),0,255) : null]);
 }
 private function sanitize(array $values): array { $blocked=['password','password_confirmation','token','secret','api_key','access_token','refresh_token']; $out=[]; foreach($values as $key=>$value){ if(in_array(strtolower((string)$key),$blocked,true)) continue; $out[$key]=is_array($value)?$this->sanitize($value):$value; } return $out; }
}
