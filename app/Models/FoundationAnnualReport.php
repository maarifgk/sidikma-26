<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['foundation_id', 'budget_year', 'work_program_report_path', 'financial_report_path'])]
class FoundationAnnualReport extends Model
{
    use HasFactory;

    public const DISK = 'foundation-annual-reports';

    public const FILE_FIELDS = [
        'work_program_report_path',
        'financial_report_path',
    ];

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    protected static function booted(): void
    {
        static::updated(function (FoundationAnnualReport $report): void {
            foreach (self::FILE_FIELDS as $field) {
                $oldPath = $report->getPrevious()[$field] ?? null;

                if (filled($oldPath) && $oldPath !== $report->getAttribute($field)) {
                    Storage::disk(self::DISK)->delete($oldPath);
                }
            }
        });

        static::deleted(function (FoundationAnnualReport $report): void {
            Storage::disk(self::DISK)->delete(
                collect(self::FILE_FIELDS)
                    ->map(fn (string $field): ?string => $report->getAttribute($field))
                    ->filter()
                    ->all(),
            );
        });
    }
}
