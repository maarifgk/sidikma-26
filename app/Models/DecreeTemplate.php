<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'paper_size',
    'orientation',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'is_active',
    'created_by',
    'updated_by',
])]
class DecreeTemplate extends Model
{
    public const PAPER_SIZES = ['A4', 'F4'];

    public const ORIENTATIONS = ['portrait', 'landscape'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
