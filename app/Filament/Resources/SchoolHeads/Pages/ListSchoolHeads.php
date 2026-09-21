<?php

namespace App\Filament\Resources\SchoolHeads\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\SchoolHeads\SchoolHeadResource;
use Filament\Actions\CreateAction;

class ListSchoolHeads extends ListRecords
{
    protected static string $resource = SchoolHeadResource::class;

    protected static ?string $title = 'Data Kepala Madrasah/Sekolah';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Lengkapi Data Kepala')->icon('heroicon-o-plus')];
    }
}
