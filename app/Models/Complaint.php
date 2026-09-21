<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'submitted_by', 'school_id', 'reference_number', 'category', 'subject',
    'description', 'attachments', 'status', 'admin_response', 'handled_by', 'handled_at',
])]
class Complaint extends Model
{
    public const DISK = 'complaints';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    protected static function booted(): void
    {
        static::creating(function (Complaint $complaint): void {
            if (blank($complaint->reference_number)) {
                $complaint->reference_number = 'PGD-'.now()->format('Ymd').'-'.str()->upper(str()->random(6));
            }
        });
    }

    public static function categoryOptions(): array
    {
        return [
            'facilities' => 'Sarana dan Prasarana',
            'workplace' => 'Lingkungan Kerja',
            'administration' => 'Administrasi',
            'ethics' => 'Etika/Perilaku',
            'safety' => 'Keamanan dan Keselamatan',
            'other' => 'Lainnya',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Terkirim',
            self::STATUS_IN_REVIEW => 'Sedang Ditinjau',
            self::STATUS_RESOLVED => 'Selesai',
            self::STATUS_REJECTED => 'Ditutup',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'handled_at' => 'datetime',
        ];
    }
}
