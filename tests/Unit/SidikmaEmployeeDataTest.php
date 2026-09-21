<?php

namespace Tests\Unit;

use App\Support\SidikmaEmployeeData;
use PHPUnit\Framework\TestCase;

class SidikmaEmployeeDataTest extends TestCase
{
    public function test_it_maps_all_legacy_employment_statuses(): void
    {
        $this->assertSame('GTY', SidikmaEmployeeData::employmentStatus(1));
        $this->assertSame('GTY_SERTIFIKASI_INPASSING', SidikmaEmployeeData::employmentStatus(2));
        $this->assertSame('PNS', SidikmaEmployeeData::employmentStatus(8));
        $this->assertNull(SidikmaEmployeeData::employmentStatus(null));
    }

    public function test_it_uses_deterministic_codes_for_invalid_or_duplicate_legacy_codes(): void
    {
        $duplicates = ['3403032004.585'];
        $this->assertSame('SIDIKMA-520', SidikmaEmployeeData::employeeCode('1', 520, $duplicates));
        $this->assertSame('SIDIKMA-143', SidikmaEmployeeData::employeeCode('3403032004.585', 143, $duplicates));
        $this->assertSame('3403032004.778', SidikmaEmployeeData::employeeCode('3403032004.778', 391, $duplicates));
    }

    public function test_it_only_preserves_valid_sixteen_digit_nuptk(): void
    {
        $this->assertSame('1259756657200013', SidikmaEmployeeData::nuptk('1259756657200013'));
        $this->assertNull(SidikmaEmployeeData::nuptk('0'));
        $this->assertNull(SidikmaEmployeeData::nuptk('2147483647'));
        $this->assertNull(SidikmaEmployeeData::nuptk('1451767668200002 / 9891990011009'));
    }

    public function test_assignment_start_uses_first_valid_legacy_date(): void
    {
        $this->assertSame('2010-12-01', SidikmaEmployeeData::assignmentStart('2010-12-01', '2024-01-01'));
        $this->assertSame('2025-01-01', SidikmaEmployeeData::assignmentStart(null, '0000-00-00 00:00:00', '2025-01-01 10:00:00'));
    }
}
