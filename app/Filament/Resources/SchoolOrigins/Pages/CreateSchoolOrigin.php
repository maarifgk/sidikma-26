<?php

namespace App\Filament\Resources\SchoolOrigins\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\SchoolOrigins\SchoolOriginResource;
use Filament\Actions\Action;

class CreateSchoolOrigin extends CreateRecord
{
    protected static string $resource = SchoolOriginResource::class;

    protected static ?string $title = 'Tambah Asal Madrasah';

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
