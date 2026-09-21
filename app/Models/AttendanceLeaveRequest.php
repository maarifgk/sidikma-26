<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'employee_id',
    'school_id',
    'leave_type',
    'start_date',
    'end_date',
    'reason',
    'attachment_path',
    'status',
    'review_notes',
    'reviewed_by',
    'reviewed_at',
])]
class AttendanceLeaveRequest extends Model
{
    public const TYPE_PERMIT = 'permit';

    public const TYPE_SICK = 'sick';

    public const TYPE_LEAVE = 'leave';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PERMIT => 'Izin',
            self::TYPE_SICK => 'Sakit',
            self::TYPE_LEAVE => 'Cuti',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }
}
