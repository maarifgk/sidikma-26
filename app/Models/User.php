<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\AddsAuditContext;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'phone_number',
    'avatar_path',
    'password',
    'is_active',
    'created_by',
    'updated_by',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    public const ROLE_ADMIN_INDUK = 'admin-induk';

    public const ROLE_ADMIN_SEKOLAH_MADRASAH = 'admin-sekolah-madrasah';

    public const ROLE_GURU_PEGAWAI = 'guru-pegawai';

    /** @use HasFactory<UserFactory> */
    use AddsAuditContext, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * Get the admin who created this user.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    /**
     * Get the admin who last updated this user.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    /**
     * Get all organizational memberships assigned to the user.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get the optional employee profile assigned to the user.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get payment invoices addressed to this account.
     */
    public function paymentInvoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    public function decreeCorrectionRequests(): HasMany
    {
        return $this->hasMany(DecreeCorrectionRequest::class, 'submitted_by');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'submitted_by');
    }

    /**
     * Get documents uploaded by the user.
     */
    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    /**
     * Get approval requests submitted by the user.
     */
    public function submittedApprovalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'submitted_by');
    }

    /**
     * Get approval requests verified by the user.
     */
    public function verifiedApprovalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'verified_by');
    }

    /**
     * Get approval requests decided by the user.
     */
    public function decidedApprovalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'decided_by');
    }

    /**
     * Get decree proposals submitted by the user.
     */
    public function submittedDecreeProposals(): HasMany
    {
        return $this->hasMany(DecreeProposal::class, 'submitted_by');
    }

    /**
     * Get the foundations assigned to the user through memberships.
     */
    public function foundations(): BelongsToMany
    {
        return $this->belongsToMany(Foundation::class, 'memberships')
            ->withPivot(['id', 'school_id', 'status', 'start_date', 'end_date'])
            ->withTimestamps();
    }

    /**
     * Get the schools assigned to the user through memberships.
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'memberships')
            ->withPivot(['id', 'foundation_id', 'status', 'start_date', 'end_date'])
            ->withTimestamps();
    }

    /**
     * Determine whether the user has unrestricted organizational access.
     */
    public function isAdminInduk(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN_INDUK);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly([
                'name',
                'email',
                'phone_number',
                'avatar_path',
                'is_active',
                'created_by',
                'updated_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $event): string => "User {$event}",
            );
    }

    /**
     * Determine whether the user may access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->hasRole(self::ROLE_ADMIN_INDUK),
            'app' => $this->hasAnyRole([
                self::ROLE_ADMIN_INDUK,
                self::ROLE_ADMIN_SEKOLAH_MADRASAH,
                self::ROLE_GURU_PEGAWAI,
            ]),
            default => false,
        };
    }

    /**
     * Get foundation IDs that the user may access.
     *
     * @return Collection<int, int>
     */
    public function accessibleFoundationIds(): Collection
    {
        if ($this->isAdminInduk()) {
            return Foundation::query()->pluck('id');
        }

        if (! $this->hasAnyRole([
            self::ROLE_ADMIN_SEKOLAH_MADRASAH,
            self::ROLE_GURU_PEGAWAI,
        ])) {
            return collect();
        }

        return $this->memberships()
            ->active()
            ->pluck('foundation_id')
            ->unique()
            ->values();
    }

    /**
     * Get school IDs that the user may access.
     *
     * @return Collection<int, int>
     */
    public function accessibleSchoolIds(): Collection
    {
        if ($this->isAdminInduk()) {
            return School::query()->pluck('id');
        }

        if (! $this->hasAnyRole([
            self::ROLE_ADMIN_SEKOLAH_MADRASAH,
            self::ROLE_GURU_PEGAWAI,
        ])) {
            return collect();
        }

        return $this->memberships()
            ->active()
            ->whereNotNull('school_id')
            ->pluck('school_id')
            ->unique()
            ->values();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
