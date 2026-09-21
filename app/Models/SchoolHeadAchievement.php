<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['school_head_id','name','level','achieved_at','organizer','description'])]
class SchoolHeadAchievement extends Model
{
    use SoftDeletes;
    public const LEVELS = ['kecamatan'=>'Kecamatan','kabupaten'=>'Kabupaten','provinsi'=>'Provinsi','nasional'=>'Nasional','internasional'=>'Internasional'];
    public function schoolHead(): BelongsTo { return $this->belongsTo(SchoolHead::class); }
    public function documents(): MorphMany { return $this->morphMany(Document::class, 'owner'); }
    protected function casts(): array { return ['achieved_at'=>'date']; }
}
