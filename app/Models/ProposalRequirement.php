<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'position',
    'number',
    'description',
    'template_label',
    'template_path',
    'is_active',
    'created_by',
    'updated_by',
])]
class ProposalRequirement extends Model
{
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
        static::updated(function (ProposalRequirement $requirement): void {
            $oldPath = $requirement->getPrevious()['template_path'] ?? null;

            if (filled($oldPath) && ! str_starts_with($oldPath, 'http') && $oldPath !== $requirement->template_path) {
                Storage::disk(ProposalRequest::DISK)->delete($oldPath);
            }
        });

        static::deleted(function (ProposalRequirement $requirement): void {
            if (filled($requirement->template_path) && ! str_starts_with($requirement->template_path, 'http')) {
                Storage::disk(ProposalRequest::DISK)->delete($requirement->template_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
