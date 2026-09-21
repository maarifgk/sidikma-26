<?php

namespace App\Filament\Resources\EducatorRecaps\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\EducatorRecaps\EducatorRecapResource;
use Filament\Actions\DeleteAction;

class EditEducatorRecap extends EditRecord
{
    protected static string $resource = EducatorRecapResource::class;

    protected static ?string $title = 'Edit Data Jumlah Tenaga Pendidik';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
