<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['school_head_id','started_at','ended_at','position','institution_name','description'])]
class SchoolHeadJobHistory extends Model
{
    use SoftDeletes;
    public function schoolHead(): BelongsTo { return $this->belongsTo(SchoolHead::class); }
    protected static function booted(): void { static::saving(function (self $record): void { if ($record->ended_at && $record->ended_at->lt($record->started_at)) throw ValidationException::withMessages(['ended_at'=>'Tanggal selesai tidak boleh sebelum tanggal mulai.']); }); }
    protected function casts(): array { return ['started_at'=>'date','ended_at'=>'date']; }
}
