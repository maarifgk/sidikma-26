<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\EditAction;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
