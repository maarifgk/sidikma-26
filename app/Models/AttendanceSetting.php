<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'check_in_start',
    'late_after',
    'check_out_start',
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
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function forSchool(int $schoolId): self
    {
        return static::query()->firstOrCreate(
            ['school_id' => $schoolId],
            [
                'check_in_start' => '06:00:00',
                'late_after' => '07:15:00',
                'check_out_start' => '14:00:00',
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

    protected function casts(): array
    {
        return [
            'office_latitude' => 'decimal:7',
            'office_longitude' => 'decimal:7',
            'radius_meters' => 'integer',
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
