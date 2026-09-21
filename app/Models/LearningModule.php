<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'foundation_id',
    'class_name',
    'module_type',
    'semester',
    'subject',
    'chapter',
    'file_path',
    'original_name',
    'uploaded_at',
    'uploaded_by',
])]
class LearningModule extends Model
{
    use HasFactory;

    public const DISK = 'learning-modules';

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (LearningModule $module): void {
            $oldPath = $module->getPrevious()['file_path'] ?? null;

            if (filled($oldPath) && $oldPath !== $module->file_path) {
                Storage::disk(self::DISK)->delete($oldPath);
            }
        });

        static::deleted(function (LearningModule $module): void {
            Storage::disk(self::DISK)->delete($module->file_path);
        });
    }
}
