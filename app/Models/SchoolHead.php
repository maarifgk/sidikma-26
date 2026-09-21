<?php

namespace App\Models;

use App\Models\Concerns\AddsAuditContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'school_id',
    'employee_id',
    'status',
    'position',
    'started_at',
    'sk_start_date',
    'sk_end_date',
    'latest_sk_number',
    'created_by',
    'updated_by',
])]
class SchoolHead extends Model
{
    use AddsAuditContext, LogsActivity, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function jobHistories(): HasMany
    {
        return $this->hasMany(SchoolHeadJobHistory::class)->latest('started_at');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(SchoolHeadCertificate::class)->latest('issued_at');
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(SchoolHeadTraining::class)->latest('started_at');
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(SchoolHeadAchievement::class)->latest('achieved_at');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'owner');
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdminInduk() ? $query : $query->whereIn('school_id', $user->accessibleSchoolIds());
    }

    public function getSkValidityStatusAttribute(): string
    {
        if (! $this->sk_end_date) {
            return 'active';
        }

        if ($this->sk_end_date->lt(today())) {
            return 'expired';
        }

        return today()->diffInDays($this->sk_end_date, false) <= config('school-head.expiring_warning_days', 90)
            ? 'expiring'
            : 'active';
    }

    public static function validityLabels(): array
    {
        return ['active' => 'AKTIF', 'expiring' => 'AKAN BERAKHIR', 'expired' => 'BERAKHIR'];
    }

    protected static function booted(): void
    {
        static::creating(function (SchoolHead $head): void {
            $head->created_by ??= auth()->id();
            $head->updated_by ??= auth()->id();
        });

        static::updating(function (SchoolHead $head): void {
            $head->updated_by = auth()->id() ?? $head->updated_by;
        });

        static::saving(function (SchoolHead $head): void {
            if ($head->sk_start_date && $head->sk_end_date && $head->sk_end_date->lt($head->sk_start_date)) {
                throw ValidationException::withMessages(['sk_end_date' => 'Tanggal berakhir SK tidak boleh sebelum tanggal mulai SK.']);
            }

            $employeeMatchesSchool = Employee::query()->whereKey($head->employee_id)->where('school_id', $head->school_id)->exists();
            if (! $employeeMatchesSchool) {
                throw ValidationException::withMessages(['employee_id' => 'Guru/pegawai harus berasal dari sekolah/madrasah yang dipilih.']);
            }

            if ($head->status === self::STATUS_ACTIVE && self::query()
                ->where('school_id', $head->school_id)
                ->where('status', self::STATUS_ACTIVE)
                ->when($head->exists, fn (Builder $query): Builder => $query->whereKeyNot($head->getKey()))
                ->exists()) {
                throw ValidationException::withMessages(['school_id' => 'Sekolah/madrasah sudah mempunyai kepala aktif.']);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('school-head')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event): string => "Data Kepala {$event}");
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'sk_start_date' => 'date',
            'sk_end_date' => 'date',
        ];
    }
}
