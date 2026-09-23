<?php

namespace App\Models;

use App\Models\Concerns\AddsAuditContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'foundation_id',
    'name',
    'npsn',
    'school_level',
    'accreditation_status',
    'accreditation_expiry_year',
    'address',
    'land_status',
    'land_area',
    'has_land_certificate',
    'has_bhpnu_ownership',
    'phone',
    'email',
    'is_active',
])]
class School extends Model
{
    protected $connection = 'sidikma_induk';

    use AddsAuditContext, HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the foundation that owns the school.
     */
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    /**
     * Get the memberships assigned to the school.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get the employees assigned to the school.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all head-of-school records for this school.
     */
    public function schoolHeads(): HasMany
    {
        return $this->hasMany(SchoolHead::class);
    }

    /**
     * Get the currently active head of this school.
     */
    public function activeSchoolHead(): HasOne
    {
        return $this->hasOne(SchoolHead::class)
            ->where('status', SchoolHead::STATUS_ACTIVE)
            ->latestOfMany('started_at');
    }

    public function decreeCorrectionRequests(): HasMany
    {
        return $this->hasMany(DecreeCorrectionRequest::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function educatorRecaps(): HasMany
    {
        return $this->hasMany(EducatorRecap::class);
    }

    public function paymentInvoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    /**
     * Get all employee assignments within the school.
     */
    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    /**
     * Get documents owned by the school.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get approval requests for the school.
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    /**
     * Limit schools to those accessible by the user.
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdminInduk()) {
            return $query;
        }

        return $query->whereKey($user->accessibleSchoolIds());
    }

    /** @return array<int, string> */
    public static function activeOptionsFor(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return static::query()
            ->accessibleTo($user)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('school')
            ->logOnly([
                'foundation_id',
                'name',
                'npsn',
                'school_level',
                'accreditation_status',
                'accreditation_expiry_year',
                'address',
                'land_status',
                'land_area',
                'has_land_certificate',
                'has_bhpnu_ownership',
                'phone',
                'email',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $event): string => "Sekolah {$event}",
            );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accreditation_expiry_year' => 'integer',
            'land_area' => 'decimal:2',
            'has_land_certificate' => 'boolean',
            'has_bhpnu_ownership' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
