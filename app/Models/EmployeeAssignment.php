<?php

namespace App\Models;

use Database\Factories\EmployeeAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'employee_id',
    'employee_position_id',
    'foundation_id',
    'school_id',
    'start_date',
    'end_date',
    'decree_number',
    'decree_date',
    'decree_period',
    'status',
    'is_primary',
    'notes',
])]
class EmployeeAssignment extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_INACTIVE = 'inactive';

    /** @use HasFactory<EmployeeAssignmentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the employee that owns the assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the position assigned to the employee.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(EmployeePosition::class, 'employee_position_id');
    }

    /**
     * Get the foundation where the employee is assigned.
     */
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    /**
     * Get the optional school where the employee is assigned.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Register model validation hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (EmployeeAssignment $assignment): void {
            $assignment->validateSchoolFoundation();
            $assignment->validateAssignmentDates();
            $assignment->validatePrimaryAssignment();
        });
    }

    protected function validateSchoolFoundation(): void
    {
        if (blank($this->school_id)) {
            return;
        }

        $schoolMatchesFoundation = School::query()
            ->whereKey($this->school_id)
            ->where('foundation_id', $this->foundation_id)
            ->exists();

        if (! $schoolMatchesFoundation) {
            throw ValidationException::withMessages([
                'school_id' => 'Sekolah/madrasah harus berada di bawah yayasan yang dipilih.',
            ]);
        }
    }

    protected function validateAssignmentDates(): void
    {
        if (blank($this->end_date) || blank($this->start_date)) {
            return;
        }

        if ($this->end_date->greaterThanOrEqualTo($this->start_date)) {
            return;
        }

        throw ValidationException::withMessages([
            'end_date' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ]);
    }

    protected function validatePrimaryAssignment(): void
    {
        if (! $this->is_primary || ($this->status !== self::STATUS_ACTIVE)) {
            return;
        }

        $hasAnotherActivePrimaryAssignment = self::query()
            ->where('employee_id', $this->employee_id)
            ->where('status', self::STATUS_ACTIVE)
            ->where('is_primary', true)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();

        if (! $hasAnotherActivePrimaryAssignment) {
            return;
        }

        throw ValidationException::withMessages([
            'is_primary' => 'Pegawai hanya boleh memiliki satu penugasan utama yang aktif.',
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'decree_date' => 'date',
            'is_primary' => 'boolean',
        ];
    }
}
