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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToRetrieveMetadata;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

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

        $storedPaths = [];
        $temporaryFiles = $this->temporaryFilesFrom($data);

        try {
            // Keep file I/O outside the database transaction. Large uploads can
            // otherwise leave the MySQL connection idle until wait_timeout and
            // cause "MySQL server has gone away" during rollback.
            $files = $this->storeUploadedFiles($data);
            $storedPaths = array_values(array_filter($files));

            $proposal = DB::transaction(function () use ($data, $actor, $files): DecreeProposal {
                $school = School::query()
                    ->accessibleTo($actor)
                    ->where('is_active', true)
                    ->findOrFail($data['school_id']);
                $position = EmployeePosition::query()
                    ->where('is_active', true)
                    ->findOrFail($data['position_id']);

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
                    ...$files,
                    'submitted_by' => $actor->getKey(),
                ]);
            });

            foreach ($temporaryFiles as $file) {
                $file->delete();
            }

            return $proposal;
        } catch (Throwable $exception) {
            Storage::disk('decree-proposals')->delete($storedPaths);

            throw $exception;
        }
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

    /** @return array<string, TemporaryUploadedFile> */
    private function temporaryFilesFrom(array $data): array
    {
        return collect($data)
            ->only(array_keys(self::fileDirectories()))
            ->filter(fn (mixed $file): bool => $file instanceof TemporaryUploadedFile)
            ->all();
    }

    /** @return array<string, string|null> */
    private function storeUploadedFiles(array $data): array
    {
        $stored = [];

        try {
            foreach (self::fileDirectories() as $field => $directory) {
                $file = $data[$field] ?? null;

                if ($file instanceof TemporaryUploadedFile) {
                    $name = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
                    $path = $file->storeAs($directory, $name, ['disk' => 'decree-proposals']);

                    if (! is_string($path) || $path === '' || ! Storage::disk('decree-proposals')->exists($path)) {
                        throw new \RuntimeException("File {$field} gagal disimpan.");
                    }

                    $stored[$field] = $path;
                } else {
                    $stored[$field] = is_string($file) ? $file : null;
                }
            }
        } catch (Throwable $exception) {
            Storage::disk('decree-proposals')->delete(array_values(array_filter($stored)));

            throw $exception;
        }

        return $stored;
    }

    /** @return array<string, string> */
    private static function fileDirectories(): array
    {
        return [
            'photo_path' => 'photos',
            'diploma_path' => 'diplomas',
            'application_letter_path' => 'application-letters',
            'service_statement_path' => 'service-statements',
            'teaching_certificate_path' => 'teaching-certificates',
            'educator_certificate_path' => 'educator-certificates',
            'task_assignment_certificate_path' => 'task-assignment-certificates',
        ];
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
