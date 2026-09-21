<?php

namespace Tests\Unit;

use App\Services\AttendanceLocationService;
use Tests\TestCase;

class AttendanceLocationServiceTest extends TestCase
{
    public function test_it_calculates_distance_and_radius_on_the_server(): void
    {
        $service = app(AttendanceLocationService::class);
        $distance = $service->calculateDistance(-7.8000000, 110.3600000, -7.8004500, 110.3600000);

        $this->assertEqualsWithDelta(50, $distance, 1);
        $this->assertTrue($service->isInsideRadius($distance, 100));
        $this->assertFalse($service->isInsideRadius($distance, 40));
    }

    public function test_accuracy_limit_is_configurable(): void
    {
        config()->set('attendance.maximum_accuracy_meters', 75);
        $service = app(AttendanceLocationService::class);

        $this->assertTrue($service->hasAcceptableAccuracy(75));
        $this->assertFalse($service->hasAcceptableAccuracy(75.01));
    }

    public function test_it_validates_a_point_against_a_polygon(): void
    {
        $polygon = [
            ['latitude' => -7.801, 'longitude' => 110.359],
            ['latitude' => -7.799, 'longitude' => 110.359],
            ['latitude' => -7.799, 'longitude' => 110.361],
            ['latitude' => -7.801, 'longitude' => 110.361],
        ];
        $service = app(AttendanceLocationService::class);

        $this->assertTrue($service->isInsidePolygon(-7.800, 110.360, $polygon));
        $this->assertFalse($service->isInsidePolygon(-7.810, 110.370, $polygon));
    }
}
