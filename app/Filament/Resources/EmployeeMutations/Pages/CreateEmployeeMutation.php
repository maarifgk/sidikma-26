<?php

namespace App\Filament\Resources\EmployeeMutations\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\EmployeeMutations\EmployeeMutationResource;
use App\Models\Employee;
use App\Models\EmployeeMutation;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Validation\ValidationException;

class CreateEmployeeMutation extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = EmployeeMutationResource::class;

    protected static ?string $title = 'Input Data Usulan Mutasi';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $employeeCode = filled($data['employee_code'] ?? null) ? $data['employee_code'] : null;
        $employee = Employee::query()
            ->when($employeeCode, fn ($query) => $query->where('employee_code', $employeeCode))
            ->when(! $employeeCode, fn ($query) => $query->whereRaw('1 = 0'))
            ->when(
                ! $user->isAdminInduk(),
                fn ($query) => $query->whereIn('school_id', $user->accessibleSchoolIds()),
            )
            ->first();

        $originSchoolId = $data['mutation_type'] !== EmployeeMutation::TYPE_INCOMING && filled($data['origin_school_id'] ?? null)
            ? (int) $data['origin_school_id']
            : null;
        $destinationSchoolId = filled($data['destination_school_id'] ?? null)
            ? (int) $data['destination_school_id']
            : null;
        $originSchool = filled($originSchoolId) ? School::query()->findOrFail($originSchoolId) : null;
        $destinationSchool = filled($destinationSchoolId) ? School::query()->findOrFail($destinationSchoolId) : null;

        if (! $user->isAdminInduk()) {
            $accessibleSchoolIds = $user->accessibleSchoolIds();
            $isAllowed = match ($data['mutation_type']) {
                EmployeeMutation::TYPE_INCOMING => $accessibleSchoolIds->contains($destinationSchoolId),
                EmployeeMutation::TYPE_INTERNAL,
                EmployeeMutation::TYPE_OUTGOING => $accessibleSchoolIds->contains($originSchoolId),
                default => false,
            };

            if (! $isAllowed) {
                throw ValidationException::withMessages([
                    'origin_school_name' => 'Madrasah asal atau tujuan tidak sesuai dengan penugasan akun Anda.',
                ]);
            }
        }

        $data['employee_id'] = $employee?->getKey();
        $data['employment_status'] = filled($employee?->employment_status)
            ? $employee->employment_status
            : $data['employment_status'];
        $data['origin_school_id'] = $originSchoolId;
        $data['destination_school_id'] = $destinationSchoolId;
        $data['origin_school_name'] = $originSchool?->name ?? ($data['origin_school_name'] ?? null);
        $data['destination_school_name'] = $destinationSchool?->name;
        $data['effective_date'] = null;
        $data['submitted_by'] = $user->getKey();

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
