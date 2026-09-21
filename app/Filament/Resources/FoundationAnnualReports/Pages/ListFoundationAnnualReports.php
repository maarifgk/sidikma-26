<?php

namespace App\Filament\Resources\FoundationAnnualReports\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\FoundationAnnualReports\FoundationAnnualReportResource;
use Filament\Actions\CreateAction;

class ListFoundationAnnualReports extends ListRecords
{
    protected static string $resource = FoundationAnnualReportResource::class;

    protected static ?string $title = 'LAPORAN TAHUNAN';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->icon('heroicon-o-plus'),
        ];
    }
}
