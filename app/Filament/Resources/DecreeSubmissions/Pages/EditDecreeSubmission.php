<?php

namespace App\Filament\Resources\DecreeSubmissions\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use App\Models\DecreeSubmission;
use App\Models\User;
use App\Services\DecreeSubmissionNotificationService;
use Illuminate\Validation\ValidationException;

class EditDecreeSubmission extends EditRecord
{
    protected static string $resource = DecreeSubmissionResource::class;

    private ?string $previousStatus = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        abort_unless(auth()->user()?->isAdminInduk(), 403);
        $this->previousStatus = $this->record->status;
        if (in_array($data['status'], [DecreeSubmission::STATUS_REJECTED, DecreeSubmission::STATUS_REVISION_REQUIRED], true) && blank($data['admin_notes'] ?? null)) {
            throw ValidationException::withMessages(['admin_notes' => 'Catatan Admin Induk wajib diisi untuk status ini.']);
        }
        if ($data['status'] === DecreeSubmission::STATUS_COMPLETED && blank($data['result_file_path'] ?? null)) {
            throw ValidationException::withMessages(['result_file_path' => 'File SK hasil PDF wajib diunggah sebelum status Selesai.']);
        }
        $data['completed_at'] = $data['status'] === DecreeSubmission::STATUS_COMPLETED ? ($this->record->completed_at ?? now()) : null;
        $data['result_original_name'] = filled($data['result_file_path'] ?? null) ? basename($data['result_file_path']) : null;
        $data['result_mime_type'] = filled($data['result_file_path'] ?? null) ? 'application/pdf' : null;
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->previousStatus === $this->record->status) {
            return;
        }
        $actor = auth()->user();
        $this->record->statusHistories()->create(['from_status' => $this->previousStatus, 'to_status' => $this->record->status, 'notes' => $this->record->admin_notes, 'changed_by' => $actor instanceof User ? $actor->getKey() : null]);
        app(DecreeSubmissionNotificationService::class)->notifyStatusChanged($this->record);
    }
}
