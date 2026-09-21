<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\Schools\SchoolResource;
use App\Models\Foundation;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected static ?string $title = 'Tambah Asal Madrasah/Sekolah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Foundation::application()->getKey();

        return $data;
    }
}
