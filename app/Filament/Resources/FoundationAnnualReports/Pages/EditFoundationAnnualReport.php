<?php

namespace App\Filament\Resources\FoundationAnnualReports\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\FoundationAnnualReports\FoundationAnnualReportResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditFoundationAnnualReport extends EditRecord
{
    protected static string $resource = FoundationAnnualReportResource::class;

    protected static ?string $title = 'Edit Laporan Tahunan';

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
