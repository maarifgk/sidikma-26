<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_invoice_id', 'user_id', 'order_id', 'transaction_id', 'idempotency_key',
    'gross_amount', 'payment_type', 'transaction_status', 'fraud_status',
    'settlement_time', 'expiry_time', 'source', 'raw_status_snapshot',
])]
class MidtransTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'settlement_time' => 'datetime',
            'expiry_time' => 'datetime',
            'raw_status_snapshot' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'payment_invoice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
