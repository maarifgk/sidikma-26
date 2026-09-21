<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'academic_year',
    'asn_certified',
    'asn_uncertified',
    'foundation_certified_inpassing',
    'foundation_uncertified',
    'total',
    'submitted_by',
])]
class EducatorRecap extends Model
{
    public const COUNT_FIELDS = [
        'asn_certified',
        'asn_uncertified',
        'foundation_certified_inpassing',
        'foundation_uncertified',
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
        static::saving(function (EducatorRecap $recap): void {
            $recap->total = collect(self::COUNT_FIELDS)
                ->sum(fn (string $field): int => (int) $recap->{$field});
        });
    }

    protected function casts(): array
    {
        return [
            'asn_certified' => 'integer',
            'asn_uncertified' => 'integer',
            'foundation_certified_inpassing' => 'integer',
            'foundation_uncertified' => 'integer',
            'total' => 'integer',
        ];
    }
}
