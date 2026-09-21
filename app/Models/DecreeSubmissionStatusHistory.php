<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['decree_submission_id', 'from_status', 'to_status', 'notes', 'changed_by'])]
class DecreeSubmissionStatusHistory extends Model
{
    public function submission(): BelongsTo { return $this->belongsTo(DecreeSubmission::class, 'decree_submission_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
