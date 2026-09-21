<?php

namespace App\Support;

final class SidikmaEmployeeData
{
    private const STATUS_MAPPING = [
        1 => 'GTY', 2 => 'GTY_SERTIFIKASI_INPASSING', 3 => 'GTY_SERTIFIKASI_NON_INPASSING',
        4 => 'GTT', 5 => 'PNS_SERTIFIKASI', 6 => 'Pegawai Tetap Yayasan',
        7 => 'Pegawai Tidak Tetap', 8 => 'PNS',
    ];

    public static function employmentStatus(mixed $legacyId): ?string
    {
        return self::STATUS_MAPPING[(int) $legacyId] ?? null;
    }

    public static function employeeType(mixed $legacyStatusId, string $positionCategory): string
    {
        if (in_array((int) $legacyStatusId, [6, 7], true)) {
            return 'pegawai';
        }

        return in_array($positionCategory, ['administrasi', 'pendukung'], true) ? 'pegawai' : 'guru';
    }

    public static function employeeCode(mixed $value, int $userId, array $duplicateCodes): string
    {
        $value = trim((string) $value);

        if ($value === '' || in_array($value, ['0', '1', '-'], true) || in_array($value, $duplicateCodes, true)) {
            return "SIDIKMA-{$userId}";
        }

        return $value;
    }

    public static function identity(mixed $value, array $placeholders = []): ?string
    {
        $value = trim((string) $value);

        return $value === '' || in_array($value, array_merge(['0', '-'], $placeholders), true) ? null : $value;
    }

    public static function nuptk(mixed $value): ?string
    {
        $value = self::identity($value, ['2147483647']);

        return $value !== null && preg_match('/^\d{16}$/', $value) === 1 ? $value : null;
    }

    public static function assignmentStart(mixed ...$dates): ?string
    {
        foreach ($dates as $value) {
            $value = trim((string) $value);
            if ($value !== '' && ! str_starts_with($value, '0000-00-00')) {
                return substr($value, 0, 10);
            }
        }

        return null;
    }

    public static function decreePeriod(mixed $legacyId): ?string
    {
        return match ((int) $legacyId) {
            1 => 'Januari', 2 => 'Juli', 3 => 'Kepala Sekolah', 4 => null, 5 => 'PNS Diperbantukan',
            default => null,
        };
    }
}
