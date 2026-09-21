<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['school_head_id','name','activity_type','organizer','started_at','ended_at','certificate_number','description'])]
class SchoolHeadTraining extends Model
{
    use SoftDeletes;
    public const TYPES = ['diklat'=>'Diklat','pelatihan'=>'Pelatihan','workshop'=>'Workshop','seminar'=>'Seminar','bimtek'=>'Bimtek','lainnya'=>'Lainnya'];
    public function schoolHead(): BelongsTo { return $this->belongsTo(SchoolHead::class); }
    public function documents(): MorphMany { return $this->morphMany(Document::class, 'owner'); }
    protected static function booted(): void { static::saving(function (self $record): void { if ($record->started_at && $record->ended_at && $record->ended_at->lt($record->started_at)) throw ValidationException::withMessages(['ended_at'=>'Tanggal selesai tidak boleh sebelum tanggal mulai.']); }); }
    protected function casts(): array { return ['started_at'=>'date','ended_at'=>'date']; }
}
