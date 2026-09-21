<?php

namespace App\Filament\Resources\SchoolHeads\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\SchoolHeads\SchoolHeadResource;
use Filament\Actions\EditAction;

class ViewSchoolHead extends ViewRecord
{
    protected static string $resource = SchoolHeadResource::class;

    protected static ?string $title = 'Data Kepala Madrasah/Sekolah';

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->label('Edit Data')];
    }
}
