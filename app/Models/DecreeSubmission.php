<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['submission_number', 'submission_date', 'school_id', 'employee_id', 'decree_submission_type_id', 'purpose', 'status', 'admin_notes', 'result_file_path', 'result_original_name', 'result_mime_type', 'completed_at', 'submitted_by', 'updated_by'])]
class DecreeSubmission extends Model
{
    use SoftDeletes;

    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REVISION_REQUIRED = 'revision_required';
    public const STATUS_REJECTED = 'rejected';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_UNDER_REVIEW => 'Dalam Peninjauan',
            self::STATUS_PROCESSING => 'Diproses',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_REVISION_REQUIRED => 'Perlu Perbaikan',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    public static function generateSubmissionNumber(): string
    {
        $prefix = 'PSK-'.now()->format('Ymd').'-';
        $last = self::withTrashed()->where('submission_number', 'like', $prefix.'%')->lockForUpdate()->max('submission_number');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdminInduk() ? $query : $query->whereIn('school_id', $user->accessibleSchoolIds());
    }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function type(): BelongsTo { return $this->belongsTo(DecreeSubmissionType::class, 'decree_submission_type_id'); }
    public function submittedBy(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function statusHistories(): HasMany { return $this->hasMany(DecreeSubmissionStatusHistory::class); }

    protected function casts(): array
    {
        return ['submission_date' => 'date', 'completed_at' => 'datetime'];
    }
}
