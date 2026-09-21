<?php

namespace App\Filament\Resources\SecretariatAgendas\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\SecretariatAgendas\SecretariatAgendaResource;
use App\Models\Foundation;
use Filament\Actions\Action;

class CreateSecretariatAgenda extends CreateRecord
{
    protected static string $resource = SecretariatAgendaResource::class;

    protected static ?string $title = 'Tambah Agenda Kesekretariatan';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Foundation::application()->getKey();

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
