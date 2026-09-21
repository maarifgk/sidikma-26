<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'is_active'])]
class DecreeSubmissionType extends Model
{
    public function submissions(): HasMany
    {
        return $this->hasMany(DecreeSubmission::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
