<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legacy_invoice_id',
    'foundation_id',
    'school_id',
    'user_id',
    'employee_id',
    'academic_year',
    'invoice_number',
    'description',
    'amount',
    'status',
    'payment_method',
    'due_date',
    'paid_at',
    'notes',
])]
class PaymentInvoice extends Model
{
    use HasFactory;

    public const STATUS_PAID = 'paid';

    public const STATUS_UNPAID = 'unpaid';

    public const METHOD_CASH = 'cash';

    public const METHOD_TRANSFER = 'bank_transfer';

    public const METHOD_QRIS = 'qris';

    /** @return array<string, string> */
    public static function paymentMethodOptions(): array
    {
        return [
            self::METHOD_CASH => 'Tunai',
            self::METHOD_TRANSFER => 'Transfer Bank',
            self::METHOD_QRIS => 'QRIS',
        ];
    }

    public function paymentMethodLabel(): string
    {
        return self::paymentMethodOptions()[$this->payment_method] ?? '-';
    }

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.str()->upper(str()->random(6));
        } while (self::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }
}
