<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_invoice_id', 'user_id', 'school_id', 'legacy_payment_id', 'legacy_invoice_id',
    'gateway_order_id', 'amount', 'payment_method', 'status', 'receipt_url',
    'installment_group', 'installment_term', 'installment_sequence', 'transacted_at', 'legacy_snapshot',
])]
class PaymentTransaction extends Model
{
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'payment_invoice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'transacted_at' => 'datetime', 'legacy_snapshot' => 'array'];
    }
}
