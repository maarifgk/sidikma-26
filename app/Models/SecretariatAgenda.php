<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['foundation_id', 'activity', 'implementation_date', 'officer', 'status', 'notes'])]
class SecretariatAgenda extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_CANCELLED = 'cancelled';

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_COMPLETED => 'Terlaksana',
            self::STATUS_PLANNED => 'Belum Terlaksana',
            self::STATUS_CANCELLED => 'Tidak Terlaksana',
        ];
    }

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    protected function casts(): array
    {
        return [
            'implementation_date' => 'date',
        ];
    }
}
