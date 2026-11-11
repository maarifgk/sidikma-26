<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'school_id',
    'workdays_per_week',
    'check_in_start',
    'late_after',
    'check_in_end',
    'check_out_start',
    'check_out_end',
    'friday_check_in_start',
    'friday_late_after',
    'friday_check_in_end',
    'friday_check_out_start',
    'friday_check_out_end',
    'check_out_end',
    'office_latitude',
    'office_longitude',
    'radius_meters',
    'maximum_accuracy_meters',
    'geofence_polygon',
    'geofence_version',
    'require_location',
    'detect_fake_gps',
    'require_selfie',
    'is_active',
    'enable_check_in',
    'enable_check_out',
    'updated_by',
])]
class AttendanceSetting extends Model
{
    protected $connection = 'sidikma_induk';

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Normalize polygons written by the legacy SIDIKMA schema. */
    public function getGeofencePolygonAttribute(mixed $value): ?array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return null;
        }

        $points = [];
        foreach ($value as $point) {
            if (! is_array($point)) {
                continue;
            }

            $latitude = $point['latitude'] ?? $point['lat'] ?? null;
            $longitude = $point['longitude'] ?? $point['lng'] ?? $point['lon'] ?? null;

            if (! is_numeric($latitude) || ! is_numeric($longitude)) {
                continue;
            }

            $points[] = [
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ];
        }

        return $points;
    }

    public static function forSchool(int $schoolId): self
    {
        return static::query()->firstOrCreate(
            ['school_id' => $schoolId],
            [
                'workdays_per_week' => 5,
                'check_in_start' => '06:00:00',
                'late_after' => '07:15:00',
                'check_in_end' => '11:59:00',
                'check_out_start' => '14:00:00',
                'check_out_end' => '17:00:00',
                'check_out_end' => '17:00:00',
                'radius_meters' => config('attendance.default_radius_meters', 100),
                'maximum_accuracy_meters' => config('attendance.maximum_accuracy_meters', 100),
                'require_location' => false,
                'detect_fake_gps' => false,
                'require_selfie' => false,
                'is_active' => true,
                'enable_check_in' => true,
                'enable_check_out' => true,
            ],
        );
    }

    public function isWorkday(Carbon $date): bool
    {
        return $date->dayOfWeekIso <= ($this->workdays_per_week === 6 ? 6 : 5);
    }

    /** @return array{check_in_start: string, late_after: string, check_in_end: string, check_out_start: string, check_out_end: string} */
    public function scheduleFor(Carbon $date): array
    {
        $friday = $date->dayOfWeekIso === 5;

        return [
            'check_in_start' => ($friday ? $this->friday_check_in_start : null) ?: $this->check_in_start,
            'late_after' => ($friday ? $this->friday_late_after : null) ?: $this->late_after,
            'check_in_end' => ($friday ? $this->friday_check_in_end : null) ?: $this->check_in_end,
            'check_out_start' => ($friday ? $this->friday_check_out_start : null) ?: $this->check_out_start,
            'check_out_end' => ($friday ? $this->friday_check_out_end : null) ?: $this->check_out_end,
        ];
    }

    protected function casts(): array
    {
        return [
            'office_latitude' => 'decimal:7',
            'office_longitude' => 'decimal:7',
            'radius_meters' => 'integer',
            'workdays_per_week' => 'integer',
            'maximum_accuracy_meters' => 'integer',
            'geofence_polygon' => 'array',
            'geofence_version' => 'integer',
            'require_location' => 'boolean',
            'detect_fake_gps' => 'boolean',
            'require_selfie' => 'boolean',
            'is_active' => 'boolean',
            'enable_check_in' => 'boolean',
            'enable_check_out' => 'boolean',
        ];
    }
}
