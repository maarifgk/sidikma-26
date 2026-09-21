<?php

namespace Tests\Feature;

use App\Models\Foundation;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_has_many_schools(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Yayasan Pendidikan Nusantara',
            'code' => 'YPN-001',
            'is_active' => true,
        ]);

        $firstSchool = $foundation->schools()->create([
            'name' => 'Madrasah Ibtidaiyah Nusantara',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        $secondSchool = $foundation->schools()->create([
            'name' => 'Madrasah Tsanawiyah Nusantara',
            'npsn' => '87654321',
            'school_level' => 'MTs',
            'is_active' => true,
        ]);

        $this->assertCount(2, $foundation->schools);
        $this->assertTrue($foundation->schools->contains($firstSchool));
        $this->assertTrue($foundation->schools->contains($secondSchool));
    }

    public function test_school_belongs_to_a_foundation(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Yayasan Pendidikan Ma\'arif',
            'code' => 'YPM-001',
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'Madrasah Aliyah Ma\'arif',
            'npsn' => '11223344',
            'school_level' => 'MA',
            'is_active' => true,
        ]);

        $this->assertTrue($school->foundation->is($foundation));
        $this->assertSame($foundation->getKey(), $school->foundation_id);
    }
}
