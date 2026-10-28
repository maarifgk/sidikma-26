<?php

namespace App\Filament\Resources\DecreeProposals\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\DecreeProposals\DecreeProposalResource;
use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDecreeProposal extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = DecreeProposalResource::class;

    protected static ?string $title = 'Input Data Usulan Guru & Pegawai Baru';

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        return DB::transaction(function () use ($data, $actor): DecreeProposal {
            $school = School::query()
                ->accessibleTo($actor)
                ->findOrFail($data['school_id']);
            $position = EmployeePosition::query()->findOrFail($data['position_id']);

            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone'],
                'password' => $data['application_password'],
                'is_active' => true,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ];

            // Hosting masih memakai kolom legacy yang wajib diisi.
            $userModel = new User;
            $userSchema = Schema::connection($userModel->getConnectionName() ?: config('database.default'));

            if ($userSchema->hasColumn($userModel->getTable(), 'nama_lengkap')) {
                $userData['nama_lengkap'] = $data['name'];
            }

            if ($userSchema->hasColumn($userModel->getTable(), 'nuptk')) {
                $userData['nuptk'] = $data['nuptk'] ?? null;
            }

            $account = User::query()->create($userData);
            $account->syncRoles([User::ROLE_GURU_PEGAWAI]);

            $employeeData = [
                'foundation_id' => $school->foundation_id,
                'school_id' => $school->getKey(),
                'user_id' => $account->getKey(),
                'employee_code' => $data['employee_code'],
                'name' => $data['name'],
                'nuptk' => $data['nuptk'] ?? null,
                'nip' => Employee::isPnsStatus($data['employment_status']) ? $data['nip'] : null,
                'rank' => Employee::isPnsStatus($data['employment_status']) ? $data['rank'] : null,
                'grade' => Employee::isPnsStatus($data['employment_status']) ? $data['grade'] : null,
                'employee_type' => $this->resolveEmployeeType($data['employment_status'], $position),
                'employment_status' => $data['employment_status'],
                'last_education' => $data['last_education'],
                'program_study' => $data['program_study'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'avatar_path' => null,
                'is_active' => true,
            ];

            $employee = new Employee;
            $employee->fill($employeeData);
            $employee->setAttribute('nuptk', $employeeData['nuptk']);
            $employee->save();

            Membership::query()->create([
                'user_id' => $account->getKey(),
                'foundation_id' => $school->foundation_id,
                'school_id' => $school->getKey(),
                'status' => 'active',
                'start_date' => $data['assignment_start_date'],
            ]);

            $employee->assignments()->create([
                'employee_position_id' => $position->getKey(),
                'foundation_id' => $school->foundation_id,
                'school_id' => $school->getKey(),
                'start_date' => $data['assignment_start_date'],
                'status' => EmployeeAssignment::STATUS_ACTIVE,
                'is_primary' => true,
            ]);

            return DecreeProposal::query()->create([
                'employee_id' => $employee->getKey(),
                'partner_admin_number' => $data['partner_admin_number'] ?? null,
                'status' => DecreeProposal::STATUS_SUBMITTED,
                'photo_path' => $data['photo_path'],
                'diploma_path' => $data['diploma_path'],
                'application_letter_path' => $data['application_letter_path'],
                'service_statement_path' => $data['service_statement_path'],
                'teaching_certificate_path' => $data['teaching_certificate_path'] ?? null,
                'educator_certificate_path' => $data['educator_certificate_path'] ?? null,
                'task_assignment_certificate_path' => $data['task_assignment_certificate_path'],
                'submitted_by' => $actor->getKey(),
            ]);
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Usulan SK baru berhasil diajukan';
    }

    private function resolveEmployeeType(string $employmentStatus, EmployeePosition $position): string
    {
        if (in_array($employmentStatus, ['GTY', 'GTT'], true)
            || $position->category === EmployeePosition::CATEGORY_TEACHING) {
            return Employee::TYPE_GURU;
        }

        return Employee::TYPE_PEGAWAI;
    }
}
