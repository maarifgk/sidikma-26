<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'employee_id',
    'employee_name',
    'school_id',
    'school_name',
    'inactive_date',
    'request_letter_path',
    'status',
    'submitted_by',
    'processed_by',
    'processed_at',
])]
class EmployeeActivityRequest extends Model
{
    public const DISK = 'employee-activities';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_COMPLETED = 'completed';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

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
            self::STATUS_SUBMITTED => 'Proses',
            self::STATUS_COMPLETED => 'Proses Selesai',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (EmployeeActivityRequest $request): void {
            $oldPath = $request->getPrevious()['request_letter_path'] ?? null;

            if (filled($oldPath) && $oldPath !== $request->request_letter_path) {
                Storage::disk(self::DISK)->delete($oldPath);
            }
        });

        static::deleted(function (EmployeeActivityRequest $request): void {
            Storage::disk(self::DISK)->delete($request->request_letter_path);
        });
    }

    protected function casts(): array
    {
        return [
            'inactive_date' => 'date',
            'processed_at' => 'datetime',
        ];
    }
}
