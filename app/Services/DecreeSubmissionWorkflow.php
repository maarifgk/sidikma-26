<?php

namespace App\Services;

use App\Models\DecreeSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecreeSubmissionWorkflow
{
    public function transition(DecreeSubmission $submission, string $status, ?string $notes, User $actor): DecreeSubmission
    {
        if (! array_key_exists($status, DecreeSubmission::statusOptions())) {
            throw ValidationException::withMessages(['status' => 'Status pengajuan tidak valid.']);
        }
        if (in_array($status, [DecreeSubmission::STATUS_REJECTED, DecreeSubmission::STATUS_REVISION_REQUIRED], true) && blank($notes)) {
            throw ValidationException::withMessages(['admin_notes' => 'Catatan Admin Induk wajib diisi untuk status ini.']);
        }
        if ($status === DecreeSubmission::STATUS_COMPLETED && blank($submission->result_file_path)) {
            throw ValidationException::withMessages(['result_file_path' => 'File SK hasil PDF wajib diunggah sebelum status Selesai.']);
        }

        $from = $submission->status;
        DB::transaction(function () use ($submission, $from, $status, $notes, $actor): void {
            $submission->update([
                'status' => $status,
                'admin_notes' => $notes,
                'completed_at' => $status === DecreeSubmission::STATUS_COMPLETED ? ($submission->completed_at ?? now()) : null,
                'updated_by' => $actor->getKey(),
            ]);
            if ($from !== $status) {
                $submission->statusHistories()->create(['from_status' => $from, 'to_status' => $status, 'notes' => $notes, 'changed_by' => $actor->getKey()]);
            }
        });

        if ($from !== $status) app(DecreeSubmissionNotificationService::class)->notifyStatusChanged($submission);

        return $submission->refresh();
    }
}
