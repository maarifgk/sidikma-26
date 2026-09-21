<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'address', 'phone', 'email', 'instagram', 'banner_path', 'board_heading', 'board_term', 'is_active'])]
class Foundation extends Model
{
    public const APPLICATION_NAME = "LP Ma'arif NU Gunungkidul";

    public const APPLICATION_CODE = 'LP-MAARIF-NU-GK';

    use HasFactory, SoftDeletes;

    public static function application(): self
    {
        $foundation = static::query()->orderBy('id')->first();

        if ($foundation instanceof self) {
            return $foundation;
        }

        $foundation = static::withTrashed()
            ->where('code', self::APPLICATION_CODE)
            ->first();

        if ($foundation instanceof self) {
            if ($foundation->trashed()) {
                $foundation->restore();
            }

            $foundation->forceFill(['is_active' => true])->save();

            return $foundation;
        }

        return static::query()->create([
            'name' => self::APPLICATION_NAME,
            'code' => self::APPLICATION_CODE,
            'is_active' => true,
        ]);
    }

    /**
     * Get the schools that belong to the foundation.
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function boardMembers(): HasMany
    {
        return $this->hasMany(FoundationBoardMember::class)->orderBy('sort_order');
    }

    public function workPrograms(): HasMany
    {
        return $this->hasMany(FoundationWorkProgram::class);
    }

    public function annualReports(): HasMany
    {
        return $this->hasMany(FoundationAnnualReport::class);
    }

    public function treasuryTransactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    public function secretariatAgendas(): HasMany
    {
        return $this->hasMany(SecretariatAgenda::class);
    }

    public function batikProducts(): HasMany
    {
        return $this->hasMany(BatikProduct::class);
    }

    public function batikOrders(): HasMany
    {
        return $this->hasMany(BatikOrder::class);
    }

    public function learningModules(): HasMany
    {
        return $this->hasMany(LearningModule::class);
    }

    public function paymentFeeItems(): HasMany
    {
        return $this->hasMany(PaymentFeeItem::class);
    }

    public function paymentTypes(): HasMany
    {
        return $this->hasMany(PaymentType::class);
    }

    public function paymentInvoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    /**
     * Get the memberships assigned to the foundation.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get the employees assigned to the foundation.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all employee assignments within the foundation.
     */
    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    /**
     * Get documents owned by the foundation.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get approval requests for the foundation.
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    /**
     * Limit foundations to those accessible by the user.
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdminInduk()) {
            return $query;
        }

        return $query->whereKey($user->accessibleFoundationIds());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'payment_fees_initialized' => 'boolean',
            'payment_types_initialized' => 'boolean',
        ];
    }
}
