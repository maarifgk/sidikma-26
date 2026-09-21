<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'legacy_mutation_id',
    'legacy_snapshot',
    'employee_id',
    'employee_code',
    'employee_name',
    'employment_status',
    'phone',
    'birth_place',
    'birth_date',
    'mutation_type',
    'effective_date',
    'origin_school_id',
    'origin_school_name',
    'destination_school_id',
    'destination_school_name',
    'request_letter_path',
    'submitted_by',
])]
class EmployeeMutation extends Model
{
    public const DISK = 'employee-mutations';

    public const TYPE_INTERNAL = 'internal';

    public const TYPE_INCOMING = 'incoming';

    public const TYPE_OUTGOING = 'outgoing';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function originSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'origin_school_id');
    }

    public function destinationSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'destination_school_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_INTERNAL => "Mutasi Internal dalam Ma'arif",
            self::TYPE_OUTGOING => "Mutasi Keluar dari Ma'arif",
            self::TYPE_INCOMING => "Mutasi Masuk ke Ma'arif",
        ];
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'effective_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (EmployeeMutation $mutation): void {
            $oldPath = $mutation->getPrevious()['request_letter_path'] ?? null;

            if (filled($oldPath) && $oldPath !== $mutation->request_letter_path) {
                Storage::disk(self::DISK)->delete($oldPath);
            }
        });

        static::deleted(function (EmployeeMutation $mutation): void {
            Storage::disk(self::DISK)->delete($mutation->request_letter_path);
        });
    }
}
