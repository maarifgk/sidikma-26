<?php

namespace App\Filament\Resources\FoundationAnnualReports\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\FoundationAnnualReports\FoundationAnnualReportResource;
use App\Models\Foundation;
use Filament\Actions\Action;

class CreateFoundationAnnualReport extends CreateRecord
{
    protected static string $resource = FoundationAnnualReportResource::class;

    protected static ?string $title = 'Tambah Laporan Tahunan';

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
