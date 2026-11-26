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

    /** @return array{user: User, reset_status: string, reused_existing: bool} */
    public function approve(DecreeProposal $proposal, User $actor): array
    {
        $this->assertSharedDatabaseConnection();
        $connection = (string) config('database.default');

        $account = DB::connection($connection)->transaction(function () use ($proposal, $actor, $connection): User|array {
            $lockedProposal = DecreeProposal::on($connection)
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedProposal instanceof DecreeProposal) {
                throw ValidationException::withMessages(['proposal' => 'Pengajuan tidak ditemukan.']);
            }

            abort_unless($actor->can('update', $lockedProposal), 403);

            if (filled($lockedProposal->employee_id)) {
                return $this->approveLegacy($lockedProposal, $actor, $connection);
            }

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

        if (is_array($account)) {
            return $account;
        }

        try {
            $resetStatus = Password::broker()->sendResetLink(['email' => $account->email]);
        } catch (\Throwable) {
            $resetStatus = 'passwords.mail_failed';
        }

        return ['user' => $account, 'reset_status' => $resetStatus, 'reused_existing' => false];
    }

    /**
     * Check whether a submitted legacy proposal can safely reuse its existing records.
     * This method is read-only and never repairs missing relationships.
     *
     * @return array{eligible: bool, reasons: array<int, string>, employee: ?Employee, user: ?User, membership: ?Membership, assignment: ?EmployeeAssignment}
     */
    public function legacyEligibility(DecreeProposal $proposal): array
    {
        $connection = (string) config('database.default');
        $reasons = [];
        $employee = filled($proposal->employee_id)
            ? Employee::on($connection)->whereKey($proposal->employee_id)->first()
            : null;
        $user = $employee?->user_id
            ? User::on($connection)->whereKey($employee->user_id)->first()
            : null;
        $schoolId = $proposal->candidateSchoolId();
        $foundationId = $proposal->candidateFoundationId();
        $membership = $user && $foundationId && $schoolId
            ? Membership::on($connection)->active()->where('user_id', $user->getKey())
                ->where('foundation_id', $foundationId)->where('school_id', $schoolId)->first()
            : null;
        $assignment = $employee && $foundationId && $schoolId
            ? EmployeeAssignment::on($connection)->where('employee_id', $employee->getKey())
                ->where('foundation_id', $foundationId)->where('school_id', $schoolId)
                ->where('status', EmployeeAssignment::STATUS_ACTIVE)->where('is_primary', true)->first()
            : null;

        if ($proposal->status !== DecreeProposal::STATUS_SUBMITTED) {
            $reasons[] = 'Pengajuan telah diproses sebelumnya.';
        }
        if (! filled($proposal->employee_id)) {
            $reasons[] = 'Pengajuan ini bukan proposal legacy.';
        }
        if (! $employee instanceof Employee) {
            $reasons[] = 'Data pegawai yang ditautkan tidak ditemukan.';
        } elseif (! $employee->is_active) {
            $reasons[] = 'Data pegawai yang ditautkan tidak aktif.';
        }
        if (! $user instanceof User) {
            $reasons[] = 'Akun pengguna yang terhubung tidak ditemukan.';
        }
        if ($schoolId === null || $foundationId === null) {
            $reasons[] = 'Identitas sekolah atau yayasan pada proposal tidak tersedia.';
        }
        if (! $membership instanceof Membership) {
            $reasons[] = 'Membership aktif untuk sekolah dan yayasan tidak ditemukan.';
        }
        if (! $assignment instanceof EmployeeAssignment) {
            $reasons[] = 'Penugasan utama aktif untuk sekolah dan yayasan tidak ditemukan.';
        }

        if ($employee instanceof Employee) {
            $this->compareLegacyIdentity($proposal, $employee, $reasons);
            if ($schoolId !== null && (int) $employee->school_id !== $schoolId) {
                $reasons[] = 'Sekolah proposal tidak sesuai dengan sekolah pegawai.';
            }
            if ($foundationId !== null && (int) $employee->foundation_id !== $foundationId) {
                $reasons[] = 'Yayasan proposal tidak sesuai dengan yayasan pegawai.';
            }
        }

        $disk = Storage::disk('decree-proposals');
        foreach (self::REQUIRED_DOCUMENTS as $field => $_required) {
            $path = $proposal->getAttribute($field);
            if (blank($path) || ! $disk->exists($path)) {
                $reasons[] = 'Dokumen wajib '.str_replace('_', ' ', $field).' belum tersedia atau file tidak ditemukan.';
            }
        }

        $duplicate = DecreeProposal::on($connection)
            ->whereKeyNot($proposal->getKey())
            ->where('employee_id', $proposal->employee_id)
            ->whereIn('status', [DecreeProposal::STATUS_SUBMITTED, DecreeProposal::STATUS_COMPLETED])
            ->exists();
        if ($duplicate) {
            $reasons[] = 'Terdapat pengajuan lain yang menggunakan pegawai yang sama.';
        }

        return [
            'eligible' => $reasons === [],
            'reasons' => array_values(array_unique($reasons)),
            'employee' => $employee,
            'user' => $user,
            'membership' => $membership,
            'assignment' => $assignment,
        ];
    }

    /** @return array{user: User, reset_status: string, reused_existing: bool} */
    private function approveLegacy(DecreeProposal $proposal, User $actor, string $connection): array
    {
        $employee = Employee::on($connection)->whereKey($proposal->employee_id)->lockForUpdate()->first();
        $user = $employee?->user_id
            ? User::on($connection)->whereKey($employee->user_id)->lockForUpdate()->first()
            : null;
        $membership = $user && $proposal->candidateFoundationId() && $proposal->candidateSchoolId()
            ? Membership::on($connection)->active()->where('user_id', $user->getKey())
                ->where('foundation_id', $proposal->candidateFoundationId())
                ->where('school_id', $proposal->candidateSchoolId())
                ->lockForUpdate()->first()
            : null;
        $assignment = $employee && $proposal->candidateFoundationId() && $proposal->candidateSchoolId()
            ? EmployeeAssignment::on($connection)->where('employee_id', $employee->getKey())
                ->where('foundation_id', $proposal->candidateFoundationId())
                ->where('school_id', $proposal->candidateSchoolId())
                ->where('status', EmployeeAssignment::STATUS_ACTIVE)
                ->where('is_primary', true)
                ->lockForUpdate()->first()
            : null;

        $legacy = $this->legacyEligibility($proposal);
        if (! $legacy['eligible'] || ! $employee || ! $user || ! $membership || ! $assignment) {
            throw ValidationException::withMessages([
                'employee_id' => implode(' ', $legacy['reasons']),
            ]);
        }

        /** @var User $user */
        $user = $legacy['user'];
        $proposal->status = DecreeProposal::STATUS_COMPLETED;
        $proposal->save();

        return ['user' => $user, 'reset_status' => 'legacy_existing', 'reused_existing' => true];
    }

    private function compareLegacyIdentity(DecreeProposal $proposal, Employee $employee, array &$reasons): void
    {
        $values = [
            'name' => [$proposal->candidate_name ?: $proposal->candidateSnapshotValue('name'), $employee->name],
            'email' => [$proposal->candidate_email ?: $proposal->candidateSnapshotValue('email'), $employee->email],
            'employee_code' => [$proposal->candidateSnapshotValue('employee_code'), $employee->employee_code],
            'nuptk' => [$proposal->candidateSnapshotValue('nuptk'), $employee->nuptk],
            'nip' => [$proposal->candidateSnapshotValue('nip'), $employee->nip],
        ];

        foreach ($values as $field => [$proposalValue, $employeeValue]) {
            if (blank($proposalValue) || blank($employeeValue)) {
                continue;
            }
            $left = strtolower(trim((string) $proposalValue));
            $right = strtolower(trim((string) $employeeValue));
            if ($left !== $right) {
                $reasons[] = 'Identitas '.$field.' proposal tidak sesuai dengan data pegawai.';
            }
        }
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
