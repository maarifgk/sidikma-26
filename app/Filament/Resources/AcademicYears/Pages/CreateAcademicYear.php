<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use Filament\Actions\Action;

class CreateAcademicYear extends CreateRecord
{
    protected static string $resource = AcademicYearResource::class;

    protected static ?string $title = 'Tambah Tahun Ajaran';

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
