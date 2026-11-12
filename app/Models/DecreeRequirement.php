<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $position
 * @property string|null $number
 * @property string $description
 * @property string|null $template_label
 * @property string|null $template_path
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 */

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
class DecreeRequirement extends Model
{
    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
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

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
