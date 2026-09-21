<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'foundation_id',
    'product_id',
    'school_id',
    'ordered_at',
    'quantity',
    'total_amount',
    'status',
    'recipient_name',
    'legacy_order_id',
    'legacy_snapshot',
])]
class BatikOrder extends Model
{
    use HasFactory;

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_READY = 'ready';

    public const STATUS_COLLECTED = 'collected';

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ORDERED => 'Dalam Pesanan',
            self::STATUS_READY => 'Siap Diambil',
            self::STATUS_COLLECTED => 'Sudah Diambil',
        ];
    }

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BatikProduct::class, 'product_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'quantity' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'legacy_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (BatikOrder $order): void {
            $order->product()->increment('stock', $order->quantity);
        });
    }
}
