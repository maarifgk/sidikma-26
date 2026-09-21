<?php

namespace App\Filament\Resources\FoundationWorkPrograms\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\FoundationWorkPrograms\FoundationWorkProgramResource;
use App\Models\Foundation;
use Filament\Actions\Action;

class CreateFoundationWorkProgram extends CreateRecord
{
    protected static string $resource = FoundationWorkProgramResource::class;

    protected static ?string $title = 'Tambah Program Kerja';

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
