<?php

namespace App\Models;

use Database\Factories\DecreeProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'legacy_proposal_id',
    'candidate_name',
    'candidate_email',
    'candidate_phone',
    'candidate_school',
    'legacy_snapshot',
    'employee_id',
    'partner_admin_number',
    'status',
    'notes',
    'photo_path',
    'diploma_path',
    'application_letter_path',
    'mwc_recommendation_request_path',
    'service_statement_path',
    'teaching_certificate_path',
    'educator_certificate_path',
    'task_assignment_certificate_path',
    'submitted_by',
])]
class DecreeProposal extends Model
{
    protected $connection = 'sidikma_induk';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    /** @use HasFactory<DecreeProposalFactory> */
    use HasFactory, SoftDeletes;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function candidateSchoolId(): ?int
    {
        $schoolId = $this->legacy_snapshot['school_id'] ?? null;

        return is_numeric($schoolId) ? (int) $schoolId : null;
    }

    public function candidateFoundationId(): ?int
    {
        $foundationId = $this->legacy_snapshot['foundation_id'] ?? null;

        return is_numeric($foundationId) ? (int) $foundationId : null;
    }

    public function candidateSnapshotValue(string $key, mixed $default = null): mixed
    {
        $snapshot = is_array($this->legacy_snapshot) ? $this->legacy_snapshot : [];

        return data_get($snapshot, $key, $default);
    }

    public function displayName(): string
    {
        return $this->employee?->name ?: ($this->candidate_name ?: '-');
    }

    public function displayEmail(): string
    {
        return $this->employee?->email ?: ($this->candidate_email ?: '-');
    }

    public function displayPhone(): string
    {
        return $this->employee?->phone ?: ($this->candidate_phone ?: '-');
    }

    public function displaySchoolName(): string
    {
        return $this->employee?->school?->name
            ?: ($this->candidate_school ?: ($this->legacy_snapshot['school_name'] ?? '-'));
    }

    public function displayEmployeeCode(): string
    {
        return $this->employee?->employee_code
            ?: ($this->legacy_snapshot['employee_code'] ?? '-');
    }

    public function displayPositionName(): string
    {
        return $this->employee?->currentAssignment?->position?->name
            ?: ($this->legacy_snapshot['position_name'] ?? '-');
    }

    public function initialNotes(): ?string
    {
        if (! is_string($this->notes) || blank($this->notes)) return null;
        $payload = json_decode($this->notes, true);
        return is_array($payload) && array_key_exists('initial', $payload)
            ? (filled($payload['initial']) ? (string) $payload['initial'] : null)
            : $this->notes;
    }

    public function rejectionReason(): ?string
    {
        if (! is_string($this->notes) || blank($this->notes)) return null;
        $payload = json_decode($this->notes, true);
        return is_array($payload) && filled($payload['rejection_reason'] ?? null)
            ? (string) $payload['rejection_reason']
            : null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'legacy_snapshot' => 'array',
        ];
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Menunggu Verifikasi',
            self::STATUS_COMPLETED => 'Proses Selesai',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }
}
