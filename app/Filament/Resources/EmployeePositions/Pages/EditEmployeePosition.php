<?php

namespace App\Filament\Resources\EmployeePositions\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\EmployeePositions\EmployeePositionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;

class EditEmployeePosition extends EditRecord
{
    protected static string $resource = EmployeePositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
