<?php

namespace App\Support;

final class SidikmaFinanceData
{
    public static function academicYear(?string $value, mixed $createdAt): ?string
    {
        $value = trim((string) $value);
        if (preg_match('/^\d{4}\/\d{4}$/', $value) === 1) {
            return $value;
        }
        $date = trim((string) $createdAt);
        if (preg_match('/^(\d{4})-(\d{2})-/', $date, $m) !== 1) {
            return null;
        }
        $year = (int) $m[1];
        $month = (int) $m[2];

        return $month >= 7 ? "$year/".($year + 1) : ($year - 1)."/$year";
    }

    public static function invoiceStatus(mixed $value): string
    {
        return SidikmaMasterData::normalized((string) $value) === 'lunas' ? 'paid' : 'unpaid';
    }

    public static function transactionStatus(mixed $value): string
    {
        return match (SidikmaMasterData::normalized((string) $value)) {
            'lunas' => 'paid', 'failed' => 'failed', default => 'pending'
        };
    }

    public static function method(mixed $value): ?string
    {
        return match (SidikmaMasterData::normalized((string) $value)) {
            'manual' => 'cash', 'online' => 'online_gateway', default => null
        };
    }

    public static function treasuryCategory(string $type, mixed $legacyId): string
    {
        $id = (int) $legacyId;
        if ($type === 'income') {
            return match ($id) {
                2 => 'student_teacher_employee_dues', 6 => 'opening_balance', default => 'other_income'
            };
        }

        return match ($id) {
            3 => 'activity_consumption', 4 => 'operational', 6 => 'internet_hosting', 7 => 'admin_honorarium', default => 'other_expense'
        };
    }
}
