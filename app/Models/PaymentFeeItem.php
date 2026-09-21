<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Fillable(['foundation_id', 'section', 'label', 'amount', 'sort_order'])]
class PaymentFeeItem extends Model
{
    use HasFactory;

    public const SECTION_DUES = 'dues';

    public const SECTION_DECREE = 'decree';

    /** @return array<string, string> */
    public static function sectionOptions(): array
    {
        return [
            self::SECTION_DUES => 'IURAN',
            self::SECTION_DECREE => 'SK YAYASAN',
        ];
    }

    /** @return Collection<int, self> */
    public static function ensureDefaults(Foundation $foundation): Collection
    {
        if (! $foundation->payment_fees_initialized) {
            DB::transaction(function () use ($foundation): void {
                $lockedFoundation = Foundation::query()->lockForUpdate()->findOrFail($foundation->getKey());

                if ($lockedFoundation->payment_fees_initialized) {
                    return;
                }

                foreach (self::defaultItems() as $item) {
                    static::query()->create([
                        'foundation_id' => $lockedFoundation->getKey(),
                        ...$item,
                    ]);
                }

                $lockedFoundation->forceFill(['payment_fees_initialized' => true])->save();
            });

            $foundation->refresh();
        }

        return static::query()
            ->where('foundation_id', $foundation->getKey())
            ->ordered()
            ->get();
    }

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE WHEN section = 'dues' THEN 0 ELSE 1 END")
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /** @return array<int, array{section: string, label: string, amount: int, sort_order: int}> */
    private static function defaultItems(): array
    {
        return [
            ['section' => self::SECTION_DUES, 'label' => 'a. Iuran Siswa Jenjang Madrasah Ibtidaiyah', 'amount' => 1000, 'sort_order' => 1],
            ['section' => self::SECTION_DUES, 'label' => 'b. Iuran Siswa Jenjang Madrasah Tsanawiyah/SMP', 'amount' => 1000, 'sort_order' => 2],
            ['section' => self::SECTION_DUES, 'label' => 'c. Iuran Kepala/Guru ASN Madrasah Bersertifikasi', 'amount' => 20000, 'sort_order' => 3],
            ['section' => self::SECTION_DUES, 'label' => 'd. Iuran Kepala/Guru ASN Madrasah Belum Sertifikasi', 'amount' => 15000, 'sort_order' => 4],
            ['section' => self::SECTION_DUES, 'label' => 'e. Iuran Kepala/Guru Madrasah Yayasan Bersertifikasi/Inpassing', 'amount' => 12000, 'sort_order' => 5],
            ['section' => self::SECTION_DUES, 'label' => 'f. Iuran Kepala/Guru Madrasah Yayasan Belum Bersertifikasi', 'amount' => 4000, 'sort_order' => 6],
            ['section' => self::SECTION_DECREE, 'label' => 'a. Penerbitan SK GTY/GTT/PTY/PTT Baru', 'amount' => 50000, 'sort_order' => 1],
            ['section' => self::SECTION_DECREE, 'label' => 'b. Perpanjangan SK Yayasan GTY/GTT/PTY/PTT', 'amount' => 0, 'sort_order' => 2],
        ];
    }
}
