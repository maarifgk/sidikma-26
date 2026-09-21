<?php

namespace App\Filament\Resources\FoundationWorkPrograms\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\FoundationWorkPrograms\FoundationWorkProgramResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditFoundationWorkProgram extends EditRecord
{
    protected static string $resource = FoundationWorkProgramResource::class;

    protected static ?string $title = 'Edit Program Kerja';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Simpan Perubahan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
