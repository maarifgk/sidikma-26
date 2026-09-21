<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'school_id',
    'school_name',
    'proposal_type',
    'request_file_path',
    'requested_amount',
    'bank_name',
    'bank_account_number',
    'bank_account_name',
    'description',
    'process_status',
    'approval_file_path',
    'approved_amount',
    'notes',
    'submitted_by',
    'processed_by',
    'processed_at',
])]
class ProposalRequest extends Model
{
    public const DISK = 'proposal-requests';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Dalam Peninjauan',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (ProposalRequest $request): void {
            foreach (['request_file_path', 'approval_file_path'] as $attribute) {
                $oldPath = $request->getPrevious()[$attribute] ?? null;

                if (filled($oldPath) && $oldPath !== $request->{$attribute}) {
                    Storage::disk(self::DISK)->delete($oldPath);
                }
            }
        });

        static::deleted(function (ProposalRequest $request): void {
            Storage::disk(self::DISK)->delete(array_filter([
                $request->request_file_path,
                $request->approval_file_path,
            ]));
        });
    }

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }
}
