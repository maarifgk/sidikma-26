<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['foundation_id', 'position', 'name', 'sort_order'])]
class FoundationBoardMember extends Model
{
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
