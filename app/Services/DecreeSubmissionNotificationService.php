<?php

namespace App\Services;

use App\Models\DecreeSubmission;
use App\Models\User;
use Filament\Notifications\Notification;

class DecreeSubmissionNotificationService
{
    public function notifyCreated(DecreeSubmission $submission): void
    {
        $submission->loadMissing(['school', 'employee']);
        $recipients = User::query()->where('is_active', true)->whereHas('roles', fn ($query) => $query->where('name', User::ROLE_ADMIN_INDUK)->where('guard_name', 'web'))->get();

        if ($recipients->isEmpty()) return;

        Notification::make()
            ->title('Pengajuan SK baru')
            ->body("Pengajuan SK baru dari {$submission->school->name} atas nama {$submission->employee->name}.")
            ->warning()
            ->sendToDatabase($recipients, isEventDispatched: true);
    }

    public function notifyStatusChanged(DecreeSubmission $submission): void
    {
        $submission->loadMissing('employee');
        $recipients = User::query()->where('is_active', true)->whereHas('memberships', fn ($query) => $query->active()->where('school_id', $submission->school_id))->whereHas('roles', fn ($query) => $query->where('name', User::ROLE_ADMIN_SEKOLAH_MADRASAH))->get();
        if ($recipients->isEmpty()) return;

        $status = DecreeSubmission::statusOptions()[$submission->status];
        $body = $submission->status === DecreeSubmission::STATUS_COMPLETED
            ? "Pengajuan SK atas nama {$submission->employee->name} telah selesai. File SK sudah dapat diunduh."
            : "Pengajuan SK atas nama {$submission->employee->name} berstatus {$status}.";

        Notification::make()->title('Status Pengajuan SK diperbarui')->body($body)->info()->sendToDatabase($recipients, isEventDispatched: true);
    }
}
