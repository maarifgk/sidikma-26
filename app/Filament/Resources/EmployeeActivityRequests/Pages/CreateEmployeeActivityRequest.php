<?php

namespace App\Filament\Resources\EmployeeActivityRequests\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\EmployeeActivityRequests\EmployeeActivityRequestResource;
use App\Models\Employee;
use App\Models\EmployeeActivityRequest;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Validation\ValidationException;

class CreateEmployeeActivityRequest extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = EmployeeActivityRequestResource::class;

    protected static ?string $title = 'Input Data Pengajuan Penonaktifan';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $schoolId = $user->accessibleSchoolIds()->first();

        if (blank($schoolId)) {
            throw ValidationException::withMessages([
                'employee_name' => 'Akun Anda belum terhubung ke madrasah/sekolah.',
            ]);
        }

        $employee = Employee::query()
            ->with('school')
            ->where('is_active', true)
            ->where('name', trim($data['employee_name']))
            ->where('school_id', $schoolId)
            ->whereIn('school_id', $user->accessibleSchoolIds())
            ->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_name' => 'Guru/pegawai aktif dengan nama dan asal madrasah tersebut tidak ditemukan.',
            ]);
        }

        $data['employee_id'] = $employee->getKey();
        $data['employee_name'] = $employee->name;
        $data['school_id'] = $employee->school_id;
        $data['school_name'] = $employee->school?->name;
        $data['status'] = EmployeeActivityRequest::STATUS_SUBMITTED;
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
