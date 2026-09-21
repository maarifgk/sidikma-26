<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use Filament\Actions\CreateAction;

class ListAcademicYears extends ListRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected static ?string $title = 'Tahun Ajaran';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add'),
        ];
    }
}
