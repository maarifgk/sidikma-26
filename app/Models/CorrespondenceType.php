<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'position',
    'number',
    'name',
    'template_label',
    'template_path',
    'is_selectable',
    'is_active',
    'created_by',
    'updated_by',
])]
class CorrespondenceType extends Model
{
    public function requests(): HasMany
    {
        return $this->hasMany(CorrespondenceRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::updated(function (CorrespondenceType $type): void {
            $oldPath = $type->getPrevious()['template_path'] ?? null;

            if (filled($oldPath) && ! str_starts_with($oldPath, 'http') && $oldPath !== $type->template_path) {
                Storage::disk(CorrespondenceRequest::DISK)->delete($oldPath);
            }
        });

        static::deleted(function (CorrespondenceType $type): void {
            if (filled($type->template_path) && ! str_starts_with($type->template_path, 'http')) {
                Storage::disk(CorrespondenceRequest::DISK)->delete($type->template_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_selectable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
