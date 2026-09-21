<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['actor_user_id','target_user_id','employee_id','school_id','action','entity_type','entity_id','old_values','new_values','ip_address','user_agent'])]
class AuditLog extends Model { public function actor(){return $this->belongsTo(User::class,'actor_user_id');} public function target(){return $this->belongsTo(User::class,'target_user_id');} public function employee(){return $this->belongsTo(Employee::class);} public function school(){return $this->belongsTo(School::class);} protected function casts(): array { return ['old_values'=>'array','new_values'=>'array']; } }
