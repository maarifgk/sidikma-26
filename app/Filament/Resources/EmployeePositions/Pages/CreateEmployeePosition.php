<?php

namespace App\Filament\Resources\EmployeePositions\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\EmployeePositions\EmployeePositionResource;

class CreateEmployeePosition extends CreateRecord
{
    protected static string $resource = EmployeePositionResource::class;
}
