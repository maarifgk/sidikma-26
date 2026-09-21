<?php

namespace Tests\Unit;

use App\Support\SidikmaSchoolData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SidikmaSchoolDataTest extends TestCase
{
    #[DataProvider('levels')]
    public function test_it_normalizes_school_levels(string $legacy, string $expected): void
    {
        $this->assertSame($expected, SidikmaSchoolData::schoolLevel($legacy));
    }

    /** @return array<string, array{string, string}> */
    public static function levels(): array
    {
        return [
            'MI' => ['Tingkat MI', 'MI'],
            'MTs' => ['Tingkat MTs', 'MTs'],
            'SMP' => ['SMP PEMBANGUNAN SEMIN', 'SMP'],
        ];
    }

    public function test_it_only_accepts_eight_digit_npsn(): void
    {
        $this->assertSame('20402263', SidikmaSchoolData::validNpsn('20402263'));
        $this->assertNull(SidikmaSchoolData::validNpsn('111234030024'));
    }

    #[DataProvider('landAreas')]
    public function test_it_normalizes_land_area(string $legacy, string $expected): void
    {
        $this->assertSame($expected, SidikmaSchoolData::decimal($legacy));
    }

    /** @return array<string, array{string, string}> */
    public static function landAreas(): array
    {
        return [
            'thousands dot' => ['1.124', '1124.00'],
            'unit' => ['375 m', '375.00'],
            'square unit' => ['565 M²', '565.00'],
        ];
    }

    public function test_it_does_not_guess_non_year_accreditation_values(): void
    {
        $this->assertSame(2029, SidikmaSchoolData::year('2029'));
        $this->assertNull(SidikmaSchoolData::year('5 Tahun'));
        $this->assertNull(SidikmaSchoolData::year('28 November'));
    }

    public function test_it_normalizes_yes_no_ownership_values(): void
    {
        $this->assertTrue(SidikmaSchoolData::yesNo('Sudah Memiliki Sertifikat'));
        $this->assertFalse(SidikmaSchoolData::yesNo('Belum Memiliki PHBNU'));
        $this->assertNull(SidikmaSchoolData::yesNo(''));
    }
}
