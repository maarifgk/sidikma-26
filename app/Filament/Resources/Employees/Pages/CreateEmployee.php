<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Employees\Schemas\EmployeeCreateForm;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected static ?string $title = 'Tambah Guru/Pegawai';

    protected static bool $canCreateAnother = false;

    private ?string $applicationPassword = null;

    private ?string $assignmentStartDate = null;

    private ?int $positionId = null;

    private ?string $decreePeriod = null;

    public function form(Schema $schema): Schema
    {
        return EmployeeCreateForm::configure($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        $school = School::query()
            ->accessibleTo($actor)
            ->findOrFail($data['school_id']);

        $this->applicationPassword = $data['application_password'] ?? null;
        $this->assignmentStartDate = $data['assignment_start_date'] ?? null;
        $this->positionId = filled($data['position_id'] ?? null)
            ? (int) $data['position_id']
            : null;
        $this->decreePeriod = $data['decree_period'] ?? null;
        unset(
            $data['application_password'],
            $data['assignment_start_date'],
            $data['position_id'],
            $data['decree_period'],
            $data['decree_period_gap'],
        );

        $data['foundation_id'] = $school->foundation_id;
        $data['employee_type'] = str_starts_with((string) $data['employment_status'], 'Pegawai')
            ? Employee::TYPE_PEGAWAI
            : Employee::TYPE_GURU;
        $data['is_active'] = true;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        return DB::transaction(function () use ($data, $actor): Employee {
            $account = User::query()->create([
                'nama_lengkap' => $data['name'],
                'nuptk' => $data['nuptk'] ?? $data['employee_code'],
                'ptt_lulus' => $data['last_education'] ?? '-',
                'p_studi' => $data['program_study'] ?? '-',
                'email' => $data['email'],
                'nama_kelas' => '-',
                'nama_jurusan' => '-',
                'tempat_lahir' => $data['birth_place'] ?? '-',
                'skbfr2025' => '-',
                'phone_number' => $data['phone'] ?? null,
                'no_tlp' => $data['phone'] ?? null,
                'password' => $this->applicationPassword,
                'role' => 0,
                'thn_pelajaran' => (string) now()->year,
                'kelas1' => 0,
                'kelas2' => 0,
                'kelas3' => 0,
                'kelas4' => 0,
                'kelas5' => 0,
                'kelas6' => 0,
                'kelas7' => 0,
                'kelas8' => 0,
                'kelas9' => 0,
                'jumlahsiswa' => 0,
                'akreditasi' => '-',
                'masaakreditasi' => '-',
                'statustanah' => '-',
                'luastanah' => '0',
                'sertifikat' => '-',
                'atasnama' => '-',
                'phbnu' => '-',
                'is_active' => true,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);
            $account->syncRoles([User::ROLE_GURU_PEGAWAI]);

            $employee = Employee::query()->create([
                ...$data,
                'user_id' => $account->getKey(),
            ]);

            Membership::query()->create([
                'user_id' => $account->getKey(),
                'foundation_id' => $employee->foundation_id,
                'school_id' => $employee->school_id,
                'status' => 'active',
                'start_date' => $this->assignmentStartDate,
            ]);

            $employee->assignments()->create([
                'employee_position_id' => $this->positionId,
                'foundation_id' => $employee->foundation_id,
                'school_id' => $employee->school_id,
                'start_date' => $this->assignmentStartDate,
                'decree_period' => $this->decreePeriod,
                'status' => EmployeeAssignment::STATUS_ACTIVE,
                'is_primary' => true,
            ]);

            return $employee;
        });
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Kembali');
    }
}
