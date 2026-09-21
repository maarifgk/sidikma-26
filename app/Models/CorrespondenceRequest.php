<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'school_id',
    'school_name',
    'correspondence_type_id',
    'type_name',
    'request_file_path',
    'response_file_path',
    'process_status',
    'notes',
    'submitted_by',
    'processed_by',
    'processed_at',
])]
class CorrespondenceRequest extends Model
{
    public const DISK = 'correspondence-requests';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_COMPLETED = 'completed';

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function correspondenceType(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceType::class);
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
            self::STATUS_COMPLETED => 'Selesai',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (CorrespondenceRequest $request): void {
            foreach (['request_file_path', 'response_file_path'] as $attribute) {
                $oldPath = $request->getPrevious()[$attribute] ?? null;

                if (filled($oldPath) && $oldPath !== $request->{$attribute}) {
                    Storage::disk(self::DISK)->delete($oldPath);
                }
            }
        });

        static::deleted(function (CorrespondenceRequest $request): void {
            Storage::disk(self::DISK)->delete(array_filter([
                $request->request_file_path,
                $request->response_file_path,
            ]));
        });
    }

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
