<?php

namespace App\Filament\Resources\SchoolOrigins\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\SchoolOrigins\SchoolOriginResource;
use Filament\Actions\CreateAction;

class ListSchoolOrigins extends ListRecords
{
    protected static string $resource = SchoolOriginResource::class;

    protected static ?string $title = 'Asal Madrasah';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->icon('heroicon-o-plus'),
        ];
    }
}
