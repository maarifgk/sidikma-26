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

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Proses',
            self::STATUS_COMPLETED => 'Proses Selesai',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }
}
