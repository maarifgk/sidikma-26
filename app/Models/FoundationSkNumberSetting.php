<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['periode','nomor_pattern','nomor_awal','nomor_berikutnya','digit_nomor','nomor_text','is_active'])]
class FoundationSkNumberSetting extends Model { protected function casts(): array { return ['is_active'=>'boolean','nomor_awal'=>'integer','nomor_berikutnya'=>'integer','digit_nomor'=>'integer']; } }
