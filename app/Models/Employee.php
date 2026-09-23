<?php

namespace App\Models;

use App\Models\Concerns\AddsAuditContext;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'foundation_id',
    'school_id',
    'user_id',
    'employee_code',
    'name',
    'avatar_path',
    'nik',
    'nip',
    'rank',
    'grade',
    'nuptk',
    'employee_type',
    'employment_status',
    'last_education',
    'program_study',
    'gender',
    'birth_place',
    'birth_date',
    'phone',
    'email',
    'address',
    'is_active',
])]
class Employee extends Model
{
    protected $connection = 'sidikma_induk';

    public const TYPE_GURU = 'guru';

    public const TYPE_PEGAWAI = 'pegawai';

    public const GENDER_MALE = 'L';

    public const GENDER_FEMALE = 'P';

    /** @return array<string, string> */
    public static function employmentStatusOptions(): array
    {
        return [
            'GTY' => 'Guru Tetap Yayasan Non Sertifikasi',
            'GTY_SERTIFIKASI_INPASSING' => 'Guru Tetap Yayasan Sertifikasi Inpassing',
            'GTY_SERTIFIKASI_NON_INPASSING' => 'Guru Tetap Yayasan Sertifikasi Non Inpassing',
            'GTT' => 'Guru Tidak Tetap',
            'PNS_SERTIFIKASI' => 'PNS Sertifikasi',
            'Pegawai Tetap Yayasan' => 'Pegawai Tetap Yayasan',
            'Pegawai Tidak Tetap' => 'Pegawai Tidak Tetap',
            'PNS' => 'PNS Non Sertifikasi',
        ];
    }

    public static function employmentStatusLabel(?string $status): string
    {
        return self::employmentStatusOptions()[$status] ?? $status ?? '-';
    }

    public static function isPnsStatus(?string $status): bool
    {
        return in_array($status, ['PNS', 'PNS_SERTIFIKASI'], true);
    }

    /** @use HasFactory<EmployeeFactory> */
    use AddsAuditContext, HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the foundation that owns the employee.
     */
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    /**
     * Get the optional school assigned to the employee.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the optional user account assigned to the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assignment history for the employee.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    /**
     * Get head-of-school records linked to this employee.
     */
    public function schoolHeads(): HasMany
    {
        return $this->hasMany(SchoolHead::class);
    }

    /**
     * Get the most recent active assignment displayed on the employee list.
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(EmployeeAssignment::class)
            ->where('status', EmployeeAssignment::STATUS_ACTIVE)
            ->latestOfMany('start_date');
    }

    /**
     * Get documents owned by the employee.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get approval requests for the employee.
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    /**
     * Get the new decree proposals submitted for the employee.
     */
    public function decreeProposals(): HasMany
    {
        return $this->hasMany(DecreeProposal::class);
    }

    public function paymentInvoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee')
            ->logOnly([
                'foundation_id',
                'school_id',
                'user_id',
                'employee_code',
                'name',
                'avatar_path',
                'nik',
                'nip',
                'nuptk',
                'employee_type',
                'employment_status',
                'last_education',
                'program_study',
                'gender',
                'birth_place',
                'birth_date',
                'phone',
                'email',
                'address',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $event): string => "Guru/Pegawai {$event}",
            );
    }

    /**
     * Register model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (Employee $employee): void {
            if (blank($employee->school_id)) {
                return;
            }

            $schoolMatchesFoundation = filled($employee->foundation_id)
                && School::query()
                    ->whereKey($employee->school_id)
                    ->where('foundation_id', $employee->foundation_id)
                    ->exists();

            if (! $schoolMatchesFoundation) {
                throw ValidationException::withMessages([
                    'school_id' => 'Sekolah/madrasah harus berada di bawah yayasan yang dipilih.',
                ]);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
