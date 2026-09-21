<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Fillable(['foundation_id', 'name', 'is_active'])]
class PaymentType extends Model
{
    public static function ensureDefaults(Foundation $foundation): Collection
    {
        if (! $foundation->payment_types_initialized) {
            DB::transaction(function () use ($foundation): void {
                $lockedFoundation = Foundation::query()->lockForUpdate()->findOrFail($foundation->getKey());

                if ($lockedFoundation->payment_types_initialized) {
                    return;
                }

                static::query()->create([
                    'foundation_id' => $lockedFoundation->getKey(),
                    'name' => 'IURAN',
                    'is_active' => false,
                ]);
                static::query()->create([
                    'foundation_id' => $lockedFoundation->getKey(),
                    'name' => 'Pembayaran Batik',
                    'is_active' => true,
                ]);

                $lockedFoundation->forceFill(['payment_types_initialized' => true])->save();
            });

            $foundation->refresh();
        }

        return static::query()
            ->where('foundation_id', $foundation->getKey())
            ->orderBy('id')
            ->get();
    }

    /** @return array<string, string> */
    public static function activeOptions(Foundation $foundation): array
    {
        return static::ensureDefaults($foundation)
            ->where('is_active', true)
            ->pluck('name', 'name')
            ->all();
    }

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
