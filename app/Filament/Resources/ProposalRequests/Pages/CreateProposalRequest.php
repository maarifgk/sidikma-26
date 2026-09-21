<?php

namespace App\Filament\Resources\ProposalRequests\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\ProposalRequests\ProposalRequestResource;
use App\Models\ProposalRequest;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;

class CreateProposalRequest extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = ProposalRequestResource::class;

    protected static ?string $title = 'Ajukan Permohonan Bantuan/Proposal';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $school = School::query()
            ->accessibleTo($user)
            ->findOrFail($data['school_id']);

        $data['school_name'] = $school->name;
        $data['process_status'] = ProposalRequest::STATUS_SUBMITTED;
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
