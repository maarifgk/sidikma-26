<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'employee_id',
    'school_id',
    'attendance_date',
    'status',
    'check_in_at',
    'check_out_at',
    'check_out_reason',
    'check_in_latitude',
    'check_in_longitude',
    'check_in_accuracy',
    'check_in_distance',
    'check_out_latitude',
    'check_out_longitude',
    'check_out_accuracy',
    'check_out_distance',
    'check_in_fake_gps', 'check_out_fake_gps', 'check_in_fake_gps_source', 'check_out_fake_gps_source', 'check_in_geofence_version', 'check_out_geofence_version',
    'check_in_selfie_path',
    'check_out_selfie_path',
    'notes',
])]
class AttendanceRecord extends Model
{
    protected $connection = 'sidikma_induk';

    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_PERMIT = 'permit';

    public const STATUS_LEAVE = 'leave';

    public const DISK = 'attendance';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PRESENT => 'Hadir',
            self::STATUS_LATE => 'Terlambat',
            self::STATUS_PERMIT => 'Izin',
            self::STATUS_LEAVE => 'Cuti',
        ];
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_out_reason' => 'string', 'check_in_fake_gps' => 'boolean', 'check_out_fake_gps' => 'boolean', 'check_in_geofence_version' => 'integer', 'check_out_geofence_version' => 'integer',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_in_accuracy' => 'decimal:2',
            'check_in_distance' => 'decimal:2',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'check_out_accuracy' => 'decimal:2',
            'check_out_distance' => 'decimal:2',
        ];
    }
}
