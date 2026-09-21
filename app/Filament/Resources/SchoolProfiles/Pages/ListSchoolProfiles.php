<?php

namespace App\Filament\Resources\SchoolProfiles\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\SchoolProfiles\SchoolProfileResource;

class ListSchoolProfiles extends ListRecords
{
    protected static string $resource = SchoolProfileResource::class;

    protected static ?string $title = 'Profile Madrasah/Sekolah';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
