<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\EmployeeAssignment;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    private ?int $positionId = null;

    private ?string $decreePeriod = null;

    private ?string $newAccountPassword = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['position_id'] = $this->record->currentAssignment?->employee_position_id;
        $data['decree_period'] = $this->record->currentAssignment?->decree_period;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->positionId = filled($data['position_id'] ?? null)
            ? (int) $data['position_id']
            : null;
        $this->decreePeriod = $data['decree_period'] ?? null;
        $this->newAccountPassword = $data['new_account_password'] ?? null;
        unset($data['position_id'], $data['decree_period'], $data['new_account_password']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (filled($this->newAccountPassword) && $this->record->user) {
            $this->record->user->update([
                'password' => $this->newAccountPassword,
                'updated_by' => auth()->id(),
            ]);
        }

        if (blank($this->positionId)) {
            return;
        }

        $assignment = $this->record->currentAssignment;

        if ($assignment) {
            $assignment->update([
                'employee_position_id' => $this->positionId,
                'foundation_id' => $this->record->foundation_id,
                'school_id' => $this->record->school_id,
                'decree_period' => $this->decreePeriod,
            ]);

            return;
        }

        $this->record->assignments()->create([
            'employee_position_id' => $this->positionId,
            'foundation_id' => $this->record->foundation_id,
            'school_id' => $this->record->school_id,
            'start_date' => now()->toDateString(),
            'decree_period' => $this->decreePeriod,
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
