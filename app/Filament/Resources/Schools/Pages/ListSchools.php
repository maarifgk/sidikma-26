<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\Schools\SchoolResource;
use Filament\Actions\CreateAction;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected static ?string $title = 'Asal Madrasah/Sekolah';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->icon('heroicon-o-plus'),
        ];
    }
}
