<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'foundation_id',
    'transaction_date',
    'transaction_type',
    'category',
    'description',
    'amount',
    'receipt_path',
])]
class TreasuryTransaction extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const DISK = 'treasury-transactions';

    /** @return array<string, string> */
    public static function incomeCategoryOptions(): array
    {
        return [
            'student_teacher_employee_dues' => 'Iuran Siswa/Guru/Pegawai',
            'opening_balance' => 'Saldo Awal',
            'donation' => 'Donasi',
            'assistance' => 'Bantuan',
            'other_income' => 'Lainnya',
        ];
    }

    /** @return array<string, string> */
    public static function expenseCategoryOptions(): array
    {
        return [
            'admin_honorarium' => 'Honor Admin',
            'activity_consumption' => 'Konsumsi Kegiatan',
            'internet_hosting' => 'WIFI/HOSTING',
            'operational' => 'Operasional',
            'other_expense' => 'Lainnya',
        ];
    }

    /** @return array<string, string> */
    public static function categoryOptions(string $type): array
    {
        return $type === self::TYPE_EXPENSE
            ? self::expenseCategoryOptions()
            : self::incomeCategoryOptions();
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions($this->transaction_type)[$this->category] ?? $this->category;
    }

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (TreasuryTransaction $transaction): void {
            $oldPath = $transaction->getPrevious()['receipt_path'] ?? null;

            if (filled($oldPath) && $oldPath !== $transaction->receipt_path) {
                Storage::disk(self::DISK)->delete($oldPath);
            }
        });

        static::deleted(function (TreasuryTransaction $transaction): void {
            if (filled($transaction->receipt_path)) {
                Storage::disk(self::DISK)->delete($transaction->receipt_path);
            }
        });
    }
}
