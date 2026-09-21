<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'document_type',
    'decree_kind',
    'decree_number',
    'decree_date',
    'notes',
    'owner_type',
    'owner_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'checksum',
    'status',
    'uploaded_by',
])]
class Document extends Model
{
    public const PRIVATE_DISK = 'documents';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the model that owns the document.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get approval requests for the document.
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    /**
     * Register document storage safeguards.
     */
    protected static function booted(): void
    {
        static::saving(function (Document $document): void {
            if ($document->disk !== self::PRIVATE_DISK) {
                throw ValidationException::withMessages([
                    'disk' => 'Dokumen wajib disimpan pada penyimpanan privat.',
                ]);
            }

            if (self::isUnsafePath($document->path)) {
                throw ValidationException::withMessages([
                    'path' => 'Lokasi dokumen tidak valid.',
                ]);
            }
        });
    }

    private static function isUnsafePath(?string $path): bool
    {
        return blank($path)
            || str_starts_with($path, '/')
            || str_contains($path, '..')
            || str_contains($path, '\\')
            || str_contains($path, "\0");
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'decree_date' => 'date',
        ];
    }
}
