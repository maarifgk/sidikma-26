<?php

namespace App\Filament\Resources\SipinterUpdates\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\SipinterUpdates\SipinterUpdateResource;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;

class CreateSipinterUpdate extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = SipinterUpdateResource::class;

    protected static ?string $title = 'Input Data dan Dokumen';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $data['school_id'] = School::query()
            ->accessibleTo($user)
            ->findOrFail($data['school_id'])
            ->getKey();
        $data['uploaded_by'] = $user->getKey();

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
