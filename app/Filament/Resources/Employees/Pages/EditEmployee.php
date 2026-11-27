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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    private ?int $positionId = null;

    private ?string $decreePeriod = null;

    private ?string $assignmentStartDate = null;

    private ?string $newAccountPassword = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['position_id'] = $this->record->currentAssignment?->employee_position_id;
        $data['decree_period'] = $this->record->currentAssignment?->decree_period;
        $data['assignment_start_date'] = $this->record->currentAssignment?->start_date?->toDateString();

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
        $this->assignmentStartDate = $data['assignment_start_date'] ?? null;
        $this->newAccountPassword = $data['new_account_password'] ?? null;
        unset($data['position_id'], $data['decree_period'], $data['assignment_start_date'], $data['new_account_password']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $record->fill($data);

            if (! $record->user_id && filled($this->newAccountPassword)) {
                $email = strtolower(trim((string) $record->email));

                if ($email === '') {
                    throw ValidationException::withMessages([
                        'email' => 'Email wajib diisi sebelum membuat akun SIDIKMA.',
                    ]);
                }

                $account = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

                if ($account) {
                    throw ValidationException::withMessages([
                        'email' => 'Email ini sudah memiliki akun SIDIKMA. Pilih akun tersebut pada field Akun User.',
                    ]);
                }

                $account = User::query()->create([
                    'name' => $record->name,
                    'nama_lengkap' => $record->name,
                    'email' => $email,
                    'password' => Hash::make($this->newAccountPassword),
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                $account->syncRoles([User::ROLE_GURU_PEGAWAI]);
                $record->user_id = $account->getKey();
            }

            $record->save();

            if ($record->user_id && array_key_exists('email', $data)) {
                User::query()
                    ->whereKey($record->user_id)
                    ->update([
                        'email' => strtolower(trim((string) $record->email)),
                        'updated_by' => auth()->id(),
                    ]);
            }

            return $record->refresh();
        });
    }

    protected function afterSave(): void
    {
        if (filled($this->newAccountPassword) && $this->record->user) {
            $this->updateExistingAccountPassword($this->record->user);
            $this->record->user->assignRole(User::ROLE_GURU_PEGAWAI);
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
                'start_date' => $this->assignmentStartDate ?: $assignment->start_date,
                'decree_period' => $this->decreePeriod,
            ]);

            return;
        }

        $this->record->assignments()->create([
            'employee_position_id' => $this->positionId,
            'foundation_id' => $this->record->foundation_id,
            'school_id' => $this->record->school_id,
            'start_date' => $this->assignmentStartDate ?: now()->toDateString(),
            'decree_period' => $this->decreePeriod,
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);
    }

    private function updateExistingAccountPassword(User $account): void
    {
        $connection = $account->getConnectionName() ?: (string) config('database.default');
        $columns = collect(Schema::connection($connection)->getColumns('users'))
            ->keyBy('name');
        $writable = static fn (string $column): bool => isset($columns[$column])
            && blank($columns[$column]['generation'] ?? null);
        $attributes = [];

        if ($writable('password')) {
            $attributes['password'] = Hash::make($this->newAccountPassword);
        }

        if ($writable('updated_by')) {
            $attributes['updated_by'] = auth()->id();
        }

        if ($writable('is_active')) {
            $attributes['is_active'] = true;
        } elseif ($writable('status')) {
            // Legacy MySQL derives is_active from status; preserve the existing
            // password-update behavior without writing the generated column.
            $attributes['status'] = 'ON';
        }

        if ($attributes !== []) {
            $account->forceFill($attributes)->save();
        }
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
