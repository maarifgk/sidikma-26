<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'academic_year',
    'k1',
    'k2',
    'k3',
    'k4',
    'k5',
    'k6',
    'k7',
    'k8',
    'k9',
    'total',
    'submitted_by',
])]
class StudentEnrollment extends Model
{
    public const GRADE_FIELDS = [
        'k1',
        'k2',
        'k3',
        'k4',
        'k5',
        'k6',
        'k7',
        'k8',
        'k9',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public static function currentAcademicYear(): string
    {
        $year = (int) now()->format('Y');
        $startYear = (int) now()->format('n') >= 7 ? $year : $year - 1;

        return $startYear.'/'.($startYear + 1);
    }

    /** @return array<string, string> */
    public static function academicYearOptions(): array
    {
        [$currentStart] = array_map('intval', explode('/', self::currentAcademicYear()));

        $years = collect(range($currentStart, $currentStart - 3))
            ->mapWithKeys(fn (int $start): array => [
                $start.'/'.($start + 1) => $start.'/'.($start + 1),
            ]);

        self::query()
            ->distinct()
            ->pluck('academic_year')
            ->each(fn (string $year) => $years->put($year, $year));

        collect(AcademicYear::activeOptions())
            ->each(fn (string $year) => $years->put($year, $year));

        return $years
            ->sortKeysDesc()
            ->all();
    }

    protected static function booted(): void
    {
        static::saving(function (StudentEnrollment $enrollment): void {
            $enrollment->total = collect(self::GRADE_FIELDS)
                ->sum(fn (string $field): int => (int) $enrollment->{$field});
        });
    }

    protected function casts(): array
    {
        return [
            'k1' => 'integer',
            'k2' => 'integer',
            'k3' => 'integer',
            'k4' => 'integer',
            'k5' => 'integer',
            'k6' => 'integer',
            'k7' => 'integer',
            'k8' => 'integer',
            'k9' => 'integer',
            'total' => 'integer',
        ];
    }
}
