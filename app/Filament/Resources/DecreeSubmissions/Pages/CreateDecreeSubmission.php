<?php

namespace App\Filament\Resources\DecreeSubmissions\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use App\Models\DecreeSubmission;
use App\Models\DecreeSubmissionType;
use App\Models\Employee;
use App\Models\School;
use App\Models\User;
use App\Services\DecreeSubmissionEligibility;
use App\Services\DecreeSubmissionNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDecreeSubmission extends CreateRecord
{
    protected static string $resource = DecreeSubmissionResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();
        $schoolId = auth()->user()?->accessibleSchoolIds()->first();
        abort_unless($schoolId && app(DecreeSubmissionEligibility::class)->schoolCanSubmit($schoolId), 403, app(DecreeSubmissionEligibility::class)->message());
    }

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH), 403);
        $schoolId = $actor->accessibleSchoolIds()->first();
        $school = School::query()->whereKey($schoolId)->where('is_active', true)->firstOrFail();
        if (! app(DecreeSubmissionEligibility::class)->schoolCanSubmit($school)) {
            throw ValidationException::withMessages(['payment' => app(DecreeSubmissionEligibility::class)->message()]);
        }
        $employee = Employee::query()->whereKey($data['employee_id'])->where('school_id', $school->getKey())->where('employee_type', Employee::TYPE_GURU)->where('is_active', true)->first();
        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Guru yang dipilih tidak berasal dari sekolah/madrasah Anda.']);
        }
        DecreeSubmissionType::query()->whereKey($data['decree_submission_type_id'])->where('is_active', true)->firstOrFail();

        $submission = DB::transaction(function () use ($data, $actor, $school, $employee): DecreeSubmission {
            $submission = DecreeSubmission::query()->create([
                'submission_number' => DecreeSubmission::generateSubmissionNumber(), 'submission_date' => $data['submission_date'], 'school_id' => $school->getKey(), 'employee_id' => $employee->getKey(), 'decree_submission_type_id' => $data['decree_submission_type_id'], 'purpose' => $data['purpose'] ?? null, 'status' => DecreeSubmission::STATUS_UNDER_REVIEW, 'submitted_by' => $actor->getKey(),
            ]);
            $submission->statusHistories()->create(['from_status' => null, 'to_status' => DecreeSubmission::STATUS_UNDER_REVIEW, 'notes' => null, 'changed_by' => $actor->getKey()]);

            return $submission;
        });
        app(DecreeSubmissionNotificationService::class)->notifyCreated($submission);

        return $submission;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengajuan SK berhasil dibuat';
    }
}
