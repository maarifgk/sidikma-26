<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\Employees\EmployeeResource;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $assignment = $this->record->currentAssignment;

        $data['position_id'] = $assignment?->employee_position_id;
        $data['decree_period'] = $assignment?->decree_period;
        $data['assignment_start_date'] = $assignment?->start_date?->toDateString();

        if ($this->record->user?->email) {
            $data['email'] = $this->record->user->email;
        }

        return $data;
    }

}
