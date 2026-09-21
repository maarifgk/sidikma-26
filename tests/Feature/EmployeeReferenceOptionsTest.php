<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeReferenceOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employment_status_options_match_the_required_list(): void
    {
        $this->assertSame([
            'Guru Tetap Yayasan Non Sertifikasi',
            'Guru Tetap Yayasan Sertifikasi Inpassing',
            'Guru Tetap Yayasan Sertifikasi Non Inpassing',
            'Guru Tidak Tetap',
            'PNS Sertifikasi',
            'Pegawai Tetap Yayasan',
            'Pegawai Tidak Tetap',
            'PNS Non Sertifikasi',
        ], array_values(Employee::employmentStatusOptions()));
    }

    public function test_all_default_assignment_options_are_available(): void
    {
        $expectedNames = collect(EmployeePosition::defaultPositions())->pluck('name');

        $this->assertCount(22, $expectedNames);
        $this->assertSame(
            $expectedNames->sort()->values()->all(),
            EmployeePosition::query()->where('is_active', true)->pluck('name')->sort()->values()->all(),
        );
    }
}
