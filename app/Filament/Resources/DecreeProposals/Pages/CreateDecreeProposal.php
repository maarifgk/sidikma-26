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
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use League\Flysystem\UnableToRetrieveMetadata;

class CreateDecreeProposal extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = DecreeProposalResource::class;

    protected static ?string $title = 'Input Data Usulan Guru & Pegawai Baru';

    protected static bool $canCreateAnother = false;

    /**
     * A failed save can leave the browser holding a Livewire temporary-file
     * reference whose physical file has already been removed. Filament then
     * tries to read its size on the next click and the request becomes a 500.
     * Convert that condition into a recoverable form notification instead.
     */
    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (UnableToRetrieveMetadata $exception) {
            report($exception);
            $this->clearTemporaryFileState();

            Notification::make()
                ->danger()
                ->title('File unggahan sudah kedaluwarsa')
                ->body('Pilih ulang semua file sebelum menekan Simpan kembali.')
                ->send();
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        return DB::transaction(function () use ($data, $actor): DecreeProposal {
            $school = School::query()
                ->accessibleTo($actor)
                ->findOrFail($data['school_id']);
            $position = EmployeePosition::query()->findOrFail($data['position_id']);

            $account = $this->createUserAccount($data, $actor);
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
                'task_assignment_certificate_path' => $data['task_assignment_certificate_path'] ?? null,
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

    private function clearTemporaryFileState(): void
    {
        foreach ([
            'photo_path',
            'diploma_path',
            'application_letter_path',
            'service_statement_path',
            'teaching_certificate_path',
            'educator_certificate_path',
            'task_assignment_certificate_path',
        ] as $field) {
            $this->data[$field] = null;
        }

        $this->form->fill($this->data);
    }

    /**
     * Create the account while supporting both the current and legacy users table.
     *
     * Legacy hosting databases contain required columns that are not part of the
     * current User model, and some deployments expose generated columns. Sending
     * the model a fixed payload makes the save dependent on one exact schema.
     */
    private function createUserAccount(array $data, User $actor): User
    {
        $account = new User;
        $schema = Schema::connection($account->getConnectionName() ?: config('database.default'));
        $table = $account->getTable();
        $columns = collect($schema->getColumns($table))->keyBy('name');

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone'],
            'password' => $data['application_password'],
            'is_active' => true,
            'created_by' => $actor->getKey(),
            'updated_by' => $actor->getKey(),
            'nama_lengkap' => $data['name'],
            'nuptk' => filled($data['nuptk'] ?? null) ? $data['nuptk'] : '0',
            'ptt_lulus' => $data['employment_status'] ?? '',
            'p_studi' => mb_substr($data['program_study'] ?? '', 0, 50),
            'nama_kelas' => '',
            'nama_jurusan' => '',
            'tempat_lahir' => $data['birth_place'] ?? '',
            'skbfr2025' => '',
            'role' => 2,
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
            'akreditasi' => '',
            'masaakreditasi' => '',
            'statustanah' => '',
            'luastanah' => '',
            'sertifikat' => '',
            'atasnama' => '',
            'phbnu' => '',
            'no_tlp' => $data['phone'],
            'status' => 'ON',
            'image' => null,
        ];

        $generatedColumns = $columns
            ->filter(fn (array $column): bool => ($column['generation'] ?? null) !== null
                || str_contains(strtoupper((string) ($column['extra'] ?? '')), 'GENERATED'))
            ->keys()
            ->all();

        $userData = collect($userData)
            ->reject(fn (mixed $value, string $column): bool => in_array($column, $generatedColumns, true))
            ->filter(fn (mixed $value, string $column): bool => $columns->has($column))
            ->all();

        $account->forceFill($userData);
        $account->save();

        return $account;
    }
}
