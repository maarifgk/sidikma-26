<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'school_id',
    'npsn',
    'school_address',
    'land_ownership',
    'land_status',
    'management_authority',
    'uses_notarial_deed',
    'request_file_path',
    'asset_file_path',
    'recommendation_file_path',
    'uploaded_by',
])]
class SipinterUpdate extends Model
{
    public const DISK = 'sipinter-updates';

    public const FILE_FIELDS = [
        'request_file_path',
        'asset_file_path',
        'recommendation_file_path',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function casts(): array
    {
        return [
            'uses_notarial_deed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (SipinterUpdate $update): void {
            foreach (self::FILE_FIELDS as $field) {
                $oldPath = $update->getPrevious()[$field] ?? null;

                if (filled($oldPath) && $oldPath !== $update->getAttribute($field)) {
                    Storage::disk(self::DISK)->delete($oldPath);
                }
            }
        });

        static::deleted(function (SipinterUpdate $update): void {
            Storage::disk(self::DISK)->delete(
                collect(self::FILE_FIELDS)
                    ->map(fn (string $field): ?string => $update->getAttribute($field))
                    ->filter()
                    ->all(),
            );
        });
    }
}
