<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'foundation_id',
    'name',
    'audience',
    'education_level',
    'image_path',
    'stock',
    'price',
    'size_label',
    'sort_order',
    'is_active',
])]
class BatikProduct extends Model
{
    use HasFactory;

    public const AUDIENCE_STUDENT = 'student';

    public const AUDIENCE_TEACHER = 'teacher';

    /** @return Collection<int, self> */
    public static function ensureDefaults(Foundation $foundation): Collection
    {
        $defaults = [
            [
                'name' => 'Batik Siswa MI',
                'audience' => self::AUDIENCE_STUDENT,
                'education_level' => 'MI',
                'price' => 58500,
                'sort_order' => 1,
            ],
            [
                'name' => 'Batik Siswa MTs/SMP',
                'audience' => self::AUDIENCE_STUDENT,
                'education_level' => 'MTs/SMP',
                'price' => 68250,
                'sort_order' => 2,
            ],
            [
                'name' => 'Batik Guru',
                'audience' => self::AUDIENCE_TEACHER,
                'education_level' => null,
                'price' => 94000,
                'sort_order' => 3,
            ],
        ];

        foreach ($defaults as $default) {
            static::query()->firstOrCreate(
                [
                    'foundation_id' => $foundation->getKey(),
                    'name' => $default['name'],
                ],
                [
                    ...$default,
                    'stock' => 0,
                    'size_label' => 'meter',
                    'is_active' => true,
                ],
            );
        }

        return static::query()
            ->where('foundation_id', $foundation->getKey())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(BatikOrder::class, 'product_id');
    }

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:2',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (BatikProduct $product): void {
            $oldPath = $product->getPrevious()['image_path'] ?? null;

            if (filled($oldPath) && $oldPath !== $product->image_path) {
                Storage::disk('public')->delete($oldPath);
            }
        });

        static::deleted(function (BatikProduct $product): void {
            if (filled($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
        });
    }
}
