<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['user_id','sk_template_id','tahun_sk','original_filename','stored_filename','file_path','mime_type','file_size','source_type','matched_by','uploaded_by'])]
class FoundationSkDocument extends Model { public function user(){return $this->belongsTo(User::class);} public function template(){return $this->belongsTo(FoundationSkTemplate::class,'sk_template_id');} public function uploadedBy(){return $this->belongsTo(User::class,'uploaded_by');} protected function casts(): array { return ['tahun_sk'=>'integer','file_size'=>'integer']; } }
