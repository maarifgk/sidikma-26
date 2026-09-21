<?php

namespace App\Filament\Resources\CorrespondenceRequests\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\CorrespondenceRequests\CorrespondenceRequestResource;
use App\Models\CorrespondenceRequest;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;

class CreateCorrespondenceRequest extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = CorrespondenceRequestResource::class;

    protected static ?string $title = 'Input Data Pengajuan Persuratan';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $school = School::query()
            ->accessibleTo($user)
            ->findOrFail($data['school_id']);
        $data['school_name'] = $school->name;
        $data['correspondence_type_id'] = null;
        $data['type_name'] = trim($data['type_name']);
        $data['process_status'] = CorrespondenceRequest::STATUS_SUBMITTED;
        $data['submitted_by'] = $user->getKey();

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
