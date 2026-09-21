<?php

namespace App\Support;

final class SidikmaSchoolData
{
    public static function schoolLevel(?string $value): ?string
    {
        $value = SidikmaMasterData::normalized((string) $value);

        return match (true) {
            str_contains($value, 'mts') => 'MTs',
            str_contains($value, 'smp') => 'SMP',
            preg_match('/(^|\s)mi($|\s)/', $value) === 1 => 'MI',
            default => null,
        };
    }

    public static function validNpsn(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{8}$/', $value) === 1 ? $value : null;
    }

    public static function year(mixed $value): ?int
    {
        $value = trim((string) $value);

        return preg_match('/^(19|20)\d{2}$/', $value) === 1 ? (int) $value : null;
    }

    public static function decimal(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s*(m2|m²|meter|m)\s*$/u', '', $value) ?? '';
        $value = preg_replace('/[^0-9,.]/', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value) === 1) {
            $value = str_replace('.', '', $value);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+$/', $value) === 1) {
            $value = str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? number_format((float) $value, 2, '.', '') : null;
    }

    public static function yesNo(mixed $value): ?bool
    {
        $value = SidikmaMasterData::normalized((string) $value);
        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'belum') || str_contains($value, 'tidak')) {
            return false;
        }

        return str_contains($value, 'sudah') || str_contains($value, 'memiliki') ? true : null;
    }
}
