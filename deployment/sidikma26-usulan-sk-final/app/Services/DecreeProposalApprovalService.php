<?php

namespace App\Services;

use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DecreeProposalApprovalService
{
    /** @var array<string, bool> */
    private const REQUIRED_DOCUMENTS = [
        'photo_path' => true,
        'diploma_path' => true,
        'application_letter_path' => true,
        'service_statement_path' => true,
        'task_assignment_certificate_path' => true,
    ];

    /** @return array{user: User, reset_status: string} */
    public function approve(DecreeProposal $proposal, User $actor): array
    {
        $this->assertSharedDatabaseConnection();
        $connection = (string) config('database.default');

        $account = DB::connection($connection)->transaction(function () use ($proposal, $actor, $connection): User {
            $lockedProposal = DecreeProposal::on($connection)
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedProposal instanceof DecreeProposal) {
                throw ValidationException::withMessages(['proposal' => 'Pengajuan tidak ditemukan.']);
            }

            abort_unless($actor->can('update', $lockedProposal), 403);
            $data = $this->validatedData($lockedProposal);

            $account = (new User)->setConnection($connection);
            $accountAttributes = [
                'name' => $data['name'], 'nama_lengkap' => $data['name'], 'nuptk' => $data['nuptk'],
                'ptt_lulus' => $data['last_education'], 'p_studi' => $data['program_study'],
                'email' => $data['email'], 'nama_kelas' => '-', 'nama_jurusan' => '-',
                'tempat_lahir' => $data['birth_place'], 'skbfr2025' => '-',
                'phone_number' => $data['phone'], 'no_tlp' => $data['phone'],
                'password' => Str::password(48), 'role' => 0,
                'thn_pelajaran' => (string) now()->year,
                'kelas1' => 0, 'kelas2' => 0, 'kelas3' => 0, 'kelas4' => 0,
                'kelas5' => 0, 'kelas6' => 0, 'kelas7' => 0, 'kelas8' => 0, 'kelas9' => 0,
                'jumlahsiswa' => 0, 'akreditasi' => '-', 'masaakreditasi' => '-',
                'statustanah' => '-', 'luastanah' => '0', 'sertifikat' => '-',
                'atasnama' => '-', 'phbnu' => '-', 'is_active' => true,
                'created_by' => $actor->getKey(), 'updated_by' => $actor->getKey(),
            ];
            $userColumns = array_flip(Schema::connection($connection)->getColumnListing('users'));
            $account->fill(array_intersect_key($accountAttributes, $userColumns));
            $account->save();
            $account->syncRoles([User::ROLE_GURU_PEGAWAI]);

            $employee = (new Employee)->setConnection($connection);
            $employee->fill([
                'foundation_id' => $data['foundation_id'], 'school_id' => $data['school_id'],
                'user_id' => $account->getKey(), 'employee_code' => $data['employee_code'],
                'name' => $data['name'], 'nuptk' => $data['nuptk'], 'nip' => $data['nip'],
                'rank' => $data['rank'], 'grade' => $data['grade'],
                'employee_type' => $this->resolveEmployeeType($data['employment_status'], $data['position']),
                'employment_status' => $data['employment_status'], 'last_education' => $data['last_education'],
                'program_study' => $data['program_study'], 'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'], 'phone' => $data['phone'],
                'email' => $data['email'], 'is_active' => true,
            ]);
            $employee->save();

            $membership = (new Membership)->setConnection($connection);
            $membership->fill([
                'user_id' => $account->getKey(), 'foundation_id' => $data['foundation_id'],
                'school_id' => $data['school_id'], 'status' => 'active',
                'start_date' => $data['assignment_start_date'],
            ]);
            $membership->save();

            $assignment = (new EmployeeAssignment)->setConnection($connection);
            $assignment->fill([
                'employee_id' => $employee->getKey(),
                'employee_position_id' => $data['position']->getKey(),
                'foundation_id' => $data['foundation_id'], 'school_id' => $data['school_id'],
                'start_date' => $data['assignment_start_date'],
                'status' => EmployeeAssignment::STATUS_ACTIVE, 'is_primary' => true,
            ]);
            $assignment->save();

            $lockedProposal->employee_id = $employee->getKey();
            $lockedProposal->status = DecreeProposal::STATUS_COMPLETED;
            $lockedProposal->save();

            return $account;
        });

        try {
            $resetStatus = Password::broker()->sendResetLink(['email' => $account->email]);
        } catch (\Throwable) {
            $resetStatus = 'passwords.mail_failed';
        }

        return ['user' => $account, 'reset_status' => $resetStatus];
    }

    public function validateForApproval(DecreeProposal $proposal): void
    {
        $this->validatedData($proposal);
    }

    /** @return array<string, mixed> */
    private function validatedData(DecreeProposal $proposal): array
    {
        $connection = (string) config('database.default');
        $proposal = DecreeProposal::on($connection)->find($proposal->getKey());
        $errors = [];

        if (! $proposal instanceof DecreeProposal) {
            throw ValidationException::withMessages([
                'proposal' => 'Pengajuan tidak ditemukan.',
            ]);
        }

        if ($proposal->status !== DecreeProposal::STATUS_SUBMITTED) {
            $errors['status'] = 'Pengajuan sudah diproses dan tidak dapat disetujui ulang.';
        }

        if (filled($proposal->employee_id)) {
            $errors['employee_id'] = 'Pengajuan lama sudah terhubung dengan data pegawai dan memerlukan penanganan khusus.';
        }

        $name = trim((string) ($proposal->candidate_name ?: $proposal->candidateSnapshotValue('name')));
        $email = strtolower(trim((string) ($proposal->candidate_email ?: $proposal->candidateSnapshotValue('email'))));
        $employeeCode = strtoupper(trim((string) $proposal->candidateSnapshotValue('employee_code')));
        $phone = trim((string) ($proposal->candidate_phone ?: $proposal->candidateSnapshotValue('phone')));
        $schoolId = $proposal->candidateSchoolId();
        $foundationId = $proposal->candidateFoundationId();
        $positionId = $proposal->candidateSnapshotValue('position_id');
        $employmentStatus = $proposal->candidateSnapshotValue('employment_status');
        $assignmentStartDate = $proposal->candidateSnapshotValue('assignment_start_date');
        $nuptk = filled($proposal->candidateSnapshotValue('nuptk'))
            ? strtoupper(trim((string) $proposal->candidateSnapshotValue('nuptk')))
            : null;

        foreach ([
            'name' => [$name, 'Nama calon guru belum tersedia.'],
            'email' => [$email, 'Email calon guru belum tersedia.'],
            'employee_code' => [$employeeCode, 'EWANUGK belum tersedia.'],
            'phone' => [$phone, 'Nomor telepon belum tersedia.'],
            'school_id' => [$schoolId, 'Identitas sekolah belum tersedia.'],
            'foundation_id' => [$foundationId, 'Identitas yayasan belum tersedia.'],
            'position_id' => [$positionId, 'Ketugasan belum tersedia.'],
            'employment_status' => [$employmentStatus, 'Status kepegawaian belum tersedia.'],
            'assignment_start_date' => [$assignmentStartDate, 'TMT pertama belum tersedia.'],
            'last_education' => [$proposal->candidateSnapshotValue('last_education'), 'Pendidikan terakhir belum tersedia.'],
            'program_study' => [$proposal->candidateSnapshotValue('program_study'), 'Program studi belum tersedia.'],
            'birth_place' => [$proposal->candidateSnapshotValue('birth_place'), 'Tempat lahir belum tersedia.'],
            'birth_date' => [$proposal->candidateSnapshotValue('birth_date'), 'Tanggal lahir belum tersedia.'],
        ] as $field => [$value, $message]) {
            if (blank($value)) {
                $errors[$field] = $message;
            }
        }

        if (filled($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email calon guru tidak valid.';
        }

        if (Employee::isPnsStatus(is_string($employmentStatus) ? $employmentStatus : null)) {
            foreach (['nip' => 'NIP', 'rank' => 'Pangkat', 'grade' => 'Golongan'] as $field => $label) {
                if (blank($proposal->candidateSnapshotValue($field))) {
                    $errors[$field] = $label.' wajib tersedia untuk status PNS.';
                }
            }
        }

        $school = $schoolId === null
            ? null
            : School::on($connection)->whereKey($schoolId)->where('is_active', true)->first();

        if (! $school instanceof School) {
            $errors['school_id'] = 'Sekolah tidak ditemukan atau sudah tidak aktif.';
        } elseif ($foundationId !== (int) $school->foundation_id) {
            $errors['foundation_id'] = 'Sekolah dan yayasan pada snapshot tidak sesuai dengan data organisasi.';
        }

        $position = is_numeric($positionId)
            ? EmployeePosition::on($connection)->whereKey((int) $positionId)->where('is_active', true)->first()
            : null;

        if (! $position instanceof EmployeePosition) {
            $errors['position_id'] = 'Ketugasan tidak ditemukan atau sudah tidak aktif.';
        }

        $disk = Storage::disk('decree-proposals');
        foreach (self::REQUIRED_DOCUMENTS as $field => $_required) {
            $path = $proposal->getAttribute($field);

            if (blank($path) || ! $disk->exists($path)) {
                $errors[$field] = 'Dokumen wajib belum tersedia atau file tidak ditemukan.';
            }
        }

        if (filled($email) && User::on($connection)->where('email', $email)->exists()) {
            $errors['email_conflict'] = 'Email sudah digunakan oleh akun lain.';
        }

        $employeeConflict = Employee::on($connection)->withTrashed()
            ->where(function ($query) use ($email, $employeeCode, $nuptk, $proposal): void {
                $query->where('employee_code', $employeeCode);

                if (filled($email)) {
                    $query->orWhere('email', $email);
                }

                if (filled($nuptk)) {
                    $query->orWhere('nuptk', $nuptk);
                }

                $nip = $proposal->candidateSnapshotValue('nip');
                if (filled($nip)) {
                    $query->orWhere('nip', trim((string) $nip));
                }
            })
            ->exists();

        if ($employeeConflict) {
            $errors['employee_conflict'] = 'Identitas calon berbenturan dengan data pegawai yang sudah tersedia.';
        }

        $duplicateProposal = DecreeProposal::on($connection)
            ->whereKeyNot($proposal->getKey())
            ->whereIn('status', [DecreeProposal::STATUS_SUBMITTED, DecreeProposal::STATUS_COMPLETED])
            ->where(function ($query) use ($email, $employeeCode): void {
                if (filled($email)) {
                    $query->where('candidate_email', $email);
                }

                if (filled($employeeCode)) {
                    $query->orWhereJsonContains('legacy_snapshot->employee_code', $employeeCode);
                }
            })
            ->exists();

        if ($duplicateProposal) {
            $errors['proposal_conflict'] = 'Pengajuan lain dengan identitas calon yang sama masih diproses atau sudah selesai.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'name' => $name, 'email' => $email, 'employee_code' => $employeeCode, 'phone' => $phone,
            'school_id' => (int) $schoolId, 'foundation_id' => (int) $foundationId,
            'position' => $position, 'employment_status' => (string) $employmentStatus,
            'nuptk' => $nuptk, 'nip' => $proposal->candidateSnapshotValue('nip'),
            'rank' => $proposal->candidateSnapshotValue('rank'), 'grade' => $proposal->candidateSnapshotValue('grade'),
            'birth_place' => (string) $proposal->candidateSnapshotValue('birth_place'),
            'birth_date' => $proposal->candidateSnapshotValue('birth_date'),
            'last_education' => (string) $proposal->candidateSnapshotValue('last_education'),
            'program_study' => (string) $proposal->candidateSnapshotValue('program_study'),
            'assignment_start_date' => $proposal->candidateSnapshotValue('assignment_start_date'),
        ];
    }

    private function resolveEmployeeType(string $employmentStatus, EmployeePosition $position): string
    {
        return in_array($employmentStatus, ['GTY', 'GTT'], true)
            || $position->category === EmployeePosition::CATEGORY_TEACHING
            ? Employee::TYPE_GURU
            : Employee::TYPE_PEGAWAI;
    }

    private function assertSharedDatabaseConnection(): void
    {
        $defaultName = (string) config('database.default');
        $default = config('database.connections.'.$defaultName, []);
        $sidikma = config('database.connections.sidikma_induk', []);
        foreach (['driver', 'host', 'port', 'database', 'username', 'unix_socket'] as $key) {
            if (($default[$key] ?? null) !== ($sidikma[$key] ?? null)) {
                throw ValidationException::withMessages([
                    'database' => 'Koneksi autentikasi dan SIDIKMA berbeda; persetujuan dihentikan untuk mencegah transaksi parsial.',
                ]);
            }
        }
    }
}
