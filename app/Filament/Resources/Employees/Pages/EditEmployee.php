<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\EmployeeAssignment;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Database\Eloquent\Model;

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

        // The linked login account is the source of truth for the email used by SIDIKMA.
        // Keep the employee profile field aligned with users.email when an account exists.
        if ($this->record->user?->email) {
            $data['email'] = $this->record->user->email;
        }

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data);
        $record->save();

        if ($record->user_id && array_key_exists('email', $data)) {
            User::query()
                ->whereKey($record->user_id)
                ->update([
                    'email' => $record->email,
                    'updated_by' => auth()->id(),
                ]);
        }

        return $record->refresh();
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
