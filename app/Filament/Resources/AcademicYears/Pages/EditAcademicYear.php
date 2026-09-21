<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditAcademicYear extends EditRecord
{
    protected static string $resource = AcademicYearResource::class;

    protected static ?string $title = 'Edit Tahun Ajaran';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Delete'),
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
