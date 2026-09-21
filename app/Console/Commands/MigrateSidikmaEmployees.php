<?php

namespace App\Console\Commands;

use App\Support\SidikmaEmployeeData;
use App\Support\SidikmaMasterData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateSidikmaEmployees extends Command
{
    protected $signature = 'sidikma:migrate-employees
                            {--dry-run : Analisis tanpa mengubah PostgreSQL}
                            {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Migrasi profil, assignment, dan membership guru/pegawai SIDIKMA';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $analysis = $this->analyze();
        $this->display($analysis, $dryRun);

        if (($analysis['invalid'] || $analysis['conflict']) && (! $this->option('force') || $dryRun)) {
            $this->error('Migrasi diblokir karena terdapat data invalid atau konflik.');

            return self::FAILURE;
        } elseif ($analysis['invalid'] || $analysis['conflict']) {
            $this->warn('Data invalid/konflik akan dilewati karena opsi --force aktif.');
        }
        if ($dryRun) {
            $this->info('DRY RUN SELESAI — PostgreSQL tidak diubah.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Migrasikan {$analysis['eligible']} profil guru/pegawai?")) {
            return self::SUCCESS;
        }

        try {
            $this->migrate($analysis);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Migrasi gagal; seluruh perubahan telah di-rollback.');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $validation = $this->analyze();
        if ($validation['eligible'] || $validation['assignment_eligible'] || $validation['membership_eligible'] || ((! $this->option('force')) && ($validation['invalid'] || $validation['conflict']))) {
            $this->error('Validasi pascamigrasi gagal. Jalankan --dry-run untuk detail.');

            return self::FAILURE;
        }
        $this->info("MIGRASI SELESAI — {$analysis['eligible']} profil, {$analysis['assignment_eligible']} assignment, dan {$analysis['membership_eligible']} membership dibuat.");

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function analyze(): array
    {
        $mysql = DB::connection('mysql_sidikma');
        $pgsql = DB::connection('pgsql');
        $source = $mysql->table('users')->whereIn('role', [0, 2])->where('id', '<>', 518)->orderBy('id')->get();
        $duplicates = $source->groupBy(fn ($row): string => trim((string) $row->nis))->filter(fn ($rows, string $key): bool => $key !== '' && $rows->count() > 1)->keys()->all();
        $foundation = $pgsql->table('foundations')->whereNull('deleted_at')->sole();
        $targetUsers = $pgsql->table('users')->get()->keyBy('id');
        $schools = $pgsql->table('schools')->whereNull('deleted_at')->get()->keyBy(fn ($row): string => SidikmaMasterData::normalized($row->name));
        $classNames = $mysql->table('kelas')->pluck('nama_kelas', 'id');
        $positionDefinitions = SidikmaMasterData::employeePositionsByName();
        $positions = $pgsql->table('employee_positions')->whereNull('deleted_at')->get()->keyBy('code');
        $tasks = $mysql->table('ketugasan')->pluck('ketugasan', 'id');
        $allEmployees = $pgsql->table('employees')->get();
        $employees = $allEmployees->whereNull('deleted_at');
        $byUser = $employees->whereNotNull('user_id')->keyBy('user_id');
        $byCode = $allEmployees->keyBy('employee_code');
        $byEmail = $allEmployees->whereNotNull('email')->keyBy(fn ($row): string => strtolower($row->email));
        $assignments = $pgsql->table('employee_assignments')->whereNull('deleted_at')->get();
        $memberships = $pgsql->table('memberships')->get();
        $rows = [];
        $stats = ['source' => $source->count(), 'target' => $employees->count(), 'eligible' => 0, 'existing' => 0, 'conflict' => 0, 'invalid' => 0, 'assignment_eligible' => 0, 'assignment_existing' => 0, 'membership_eligible' => 0, 'membership_existing' => 0, 'fallback_codes' => 0, 'null_nuptk' => 0, 'missing_school' => 0, 'fallback_start' => 0];

        foreach ($source as $legacy) {
            $userId = (int) $legacy->id;
            $user = $targetUsers->get($userId);
            $taskName = $tasks->get($legacy->ketugasan);
            $definition = $positionDefinitions[SidikmaMasterData::normalized((string) $taskName)] ?? null;
            $position = $definition ? $positions->get($definition['code']) : null;
            $schoolName = $classNames->get($legacy->kelas_id);
            $school = $schoolName ? $schools->get(SidikmaMasterData::normalized($schoolName)) : null;
            $code = SidikmaEmployeeData::employeeCode($legacy->nis, $userId, $duplicates);
            $nuptk = SidikmaEmployeeData::nuptk($legacy->nuptk);
            $nip = SidikmaEmployeeData::identity($legacy->nip);
            $email = strtolower(trim((string) $legacy->email));
            $start = SidikmaEmployeeData::assignmentStart($legacy->tmt, $legacy->created_at, $legacy->updated_at);
            $existing = $byUser->get($userId);
            $codeOwner = $byCode->get($code);
            $emailOwner = $email !== '' ? $byEmail->get($email) : null;
            $codeExists = $pgsql->table('employees')
                ->where('employee_code', $code)
                ->when($existing, fn ($query) => $query->where('id', '<>', $existing->id))
                ->exists();
            $emailExists = $email !== '' && $pgsql->table('employees')
                ->where('email', $email)
                ->when($existing, fn ($query) => $query->where('id', '<>', $existing->id))
                ->exists();
            $status = 'ELIGIBLE';

            if (! $user || ! $position || ! $start) {
                $status = 'INVALID';
                $stats['invalid']++;
            } elseif (($codeOwner && (int) $codeOwner->user_id !== $userId) || ($emailOwner && (int) $emailOwner->user_id !== $userId) || $codeExists || $emailExists) {
                $status = 'CONFLICT';
                $stats['conflict']++;
            } elseif ($existing) {
                $status = 'EXISTING';
                $stats['existing']++;
            } else {
                $stats['eligible']++;
            }

            if (str_starts_with($code, 'SIDIKMA-')) {
                $stats['fallback_codes']++;
            }
            if ($nuptk === null) {
                $stats['null_nuptk']++;
            }
            if ($school === null) {
                $stats['missing_school']++;
            }
            if (empty($legacy->tmt)) {
                $stats['fallback_start']++;
            }

            $employeeId = $existing?->id;
            $assignmentExists = $employeeId && $assignments->contains(fn ($row): bool => (int) $row->employee_id === (int) $employeeId && (int) $row->employee_position_id === (int) $position?->id && (int) ($row->school_id ?? 0) === (int) ($school?->id ?? 0) && $row->status === 'active');
            $membershipExists = $school && $memberships->contains(fn ($row): bool => (int) $row->user_id === $userId && (int) $row->school_id === (int) $school->id && $row->status === 'active');
            if (in_array($status, ['ELIGIBLE', 'EXISTING'], true)) {
                $assignmentExists ? $stats['assignment_existing']++ : $stats['assignment_eligible']++;
                if ($school) {
                    $membershipExists ? $stats['membership_existing']++ : $stats['membership_eligible']++;
                }
            }

            $rows[] = [
                'legacy_id' => $userId, 'employee_id' => $employeeId, 'status' => $status,
                'employee' => [
                    'foundation_id' => (int) $foundation->id, 'school_id' => $school?->id, 'user_id' => $userId,
                    'employee_code' => $code, 'name' => trim((string) $legacy->nama_lengkap), 'avatar_path' => $this->nullable($legacy->image),
                    'nik' => null, 'nip' => $nip, 'rank' => $this->nullable($legacy->pangkat_golongan), 'grade' => null, 'nuptk' => $nuptk,
                    'employee_type' => SidikmaEmployeeData::employeeType($legacy->jurusan_id, $definition['category']),
                    'employment_status' => SidikmaEmployeeData::employmentStatus($legacy->jurusan_id),
                    'last_education' => $this->nullable($legacy->ptt_lulus), 'program_study' => $this->nullable($legacy->p_studi),
                    'gender' => null, 'birth_place' => $this->nullable($legacy->tempat_lahir), 'birth_date' => $this->date($legacy->tgl_lahir),
                    'phone' => $this->nullable($legacy->no_tlp), 'email' => $email, 'address' => $this->nullable($legacy->alamat), 'is_active' => $legacy->status === 'ON',
                ],
                'assignment' => ['employee_position_id' => (int) $position?->id, 'foundation_id' => (int) $foundation->id, 'school_id' => $school?->id, 'start_date' => $start, 'end_date' => null, 'decree_number' => null, 'decree_date' => null, 'decree_period' => SidikmaEmployeeData::decreePeriod($legacy->periode), 'status' => 'active', 'is_primary' => true, 'notes' => empty($legacy->tmt) ? 'Tanggal mulai fallback dari created_at SIDIKMA.' : null],
                'assignment_exists' => (bool) $assignmentExists, 'membership_exists' => (bool) $membershipExists,
            ];
        }

        return $stats + ['rows' => $rows];
    }

    /** @param array<string, mixed> $a */
    private function display(array $a, bool $dryRun): void
    {
        $this->info($dryRun ? 'DRY RUN MIGRASI GURU/PEGAWAI SIDIKMA' : 'MIGRASI GURU/PEGAWAI SIDIKMA');
        $this->table(['Pemeriksaan', 'Jumlah'], collect($a)->only(['source', 'target', 'eligible', 'existing', 'conflict', 'invalid', 'assignment_eligible', 'assignment_existing', 'membership_eligible', 'membership_existing', 'fallback_codes', 'null_nuptk', 'missing_school', 'fallback_start'])->map(fn ($value, $key): array => [$key, $value])->values()->all());
        $problems = collect($a['rows'])->whereIn('status', ['INVALID', 'CONFLICT'])->map(fn ($row): array => [$row['legacy_id'], $row['employee']['name'], $row['employee']['employee_code'], $row['status']])->all();
        if ($problems) {
            $this->table(['ID', 'Nama', 'Kode', 'Status'], $problems);
        }
    }

    /** @param array<string, mixed> $a */
    private function migrate(array $a): void
    {
        DB::connection('pgsql')->transaction(function () use ($a): void {
            $now = now();
            foreach ($a['rows'] as $row) {
                if (! in_array($row['status'], ['ELIGIBLE', 'EXISTING'], true)) {
                    continue;
                }

                $employeeId = $row['employee_id'];
                if ($row['status'] === 'ELIGIBLE') {
                    $employeeId = DB::connection('pgsql')->table('employees')->insertGetId($row['employee'] + ['created_at' => $now, 'updated_at' => $now]);
                }
                if (! $row['assignment_exists']) {
                    DB::connection('pgsql')->table('employee_assignments')->insert($row['assignment'] + ['employee_id' => $employeeId, 'created_at' => $now, 'updated_at' => $now]);
                }
                if ($row['employee']['school_id'] && ! $row['membership_exists']) {
                    DB::connection('pgsql')->table('memberships')->insert(['user_id' => $row['legacy_id'], 'foundation_id' => $row['employee']['foundation_id'], 'school_id' => $row['employee']['school_id'], 'status' => 'active', 'start_date' => $row['assignment']['start_date'], 'end_date' => null, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        });
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?string
    {
        $value = $this->nullable($value);

        return $value === null || str_starts_with($value, '0000-00-00') ? null : substr($value, 0, 10);
    }
}
