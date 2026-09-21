<?php

namespace App\Filament\Resources\SchoolOrigins\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\SchoolOrigins\SchoolOriginResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;

class EditSchoolOrigin extends EditRecord
{
    protected static string $resource = SchoolOriginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
