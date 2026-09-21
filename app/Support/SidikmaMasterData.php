<?php

namespace App\Support;

use App\Models\EmployeePosition;
use Illuminate\Support\Str;

final class SidikmaMasterData
{
    /** @return array<string, array{code: string, name: string, category: string}> */
    public static function employeePositionsByName(): array
    {
        return collect(EmployeePosition::defaultPositions())
            ->keyBy(fn (array $position): string => self::normalized($position['name']))
            ->all();
    }

    public static function generalPaymentType(string $legacyName): ?string
    {
        $name = self::normalized($legacyName);

        return match (true) {
            str_contains($name, 'batik') => 'Pembayaran Batik',
            str_contains($name, 'sk ') || str_ends_with($name, ' sk') => 'Pembayaran SK',
            str_contains($name, 'buku') => 'Pembayaran Buku Ke-NU-an',
            str_contains($name, 'iuran') => 'IURAN',
            default => null,
        };
    }

    public static function normalized(string $value): string
    {
        return Str::of($value)->squish()->lower()->value();
    }
}
