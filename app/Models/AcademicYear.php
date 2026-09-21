<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'is_active'])]
class AcademicYear extends Model
{
    /** @return array<string, string> */
    public static function activeOptions(): array
    {
        if (! Schema::hasTable('academic_years')) {
            return [];
        }

        return self::query()
            ->where('is_active', true)
            ->orderByDesc('name')
            ->pluck('name', 'name')
            ->all();
    }

    public static function isValidPeriod(string $value): bool
    {
        if (preg_match('/^(\d{4})\/(\d{4})$/', $value, $matches) !== 1) {
            return false;
        }

        return ((int) $matches[2]) === ((int) $matches[1]) + 1;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
