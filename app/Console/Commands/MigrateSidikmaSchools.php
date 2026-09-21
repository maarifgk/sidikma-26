<?php

namespace App\Console\Commands;

use App\Support\SidikmaMasterData;
use App\Support\SidikmaSchoolData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateSidikmaSchools extends Command
{
    protected $signature = 'sidikma:migrate-schools
                            {--dry-run : Analisis tanpa mengubah PostgreSQL}
                            {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Migrasi sekolah dan membership admin SIDIKMA ke PostgreSQL';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $analysis = $this->analyze();
        $this->display($analysis, $dryRun);

        if ($analysis['invalid'] > 0 || $analysis['conflict'] > 0) {
            $this->error('Migrasi diblokir karena terdapat data invalid atau konflik.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('DRY RUN SELESAI — PostgreSQL tidak diubah.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Buat {$analysis['eligible']} sekolah dan {$analysis['membership_eligible']} membership admin?")) {
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
        if ($validation['eligible'] !== 0 || $validation['membership_eligible'] !== 0 || $validation['invalid'] !== 0 || $validation['conflict'] !== 0) {
            $this->error('Validasi pascamigrasi gagal. Jalankan --dry-run untuk detail.');

            return self::FAILURE;
        }

        $this->info("MIGRASI SELESAI — {$analysis['eligible']} sekolah dan {$analysis['membership_eligible']} membership dibuat.");

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function analyze(): array
    {
        $mysql = DB::connection('mysql_sidikma');
        $pgsql = DB::connection('pgsql');
        $foundation = $pgsql->table('foundations')->whereNull('deleted_at')->get();
        $classes = $mysql->table('kelas')->orderBy('id')->get();
        $users = $mysql->table('users')->where('role', 3)->get()->keyBy('kelas_id');
        $sipinter = $mysql->table('updatesipinter')->get()->keyBy(fn ($row): int => (int) $row->kelas);
        $facilities = $mysql->table('sarpras')->get()->keyBy(fn ($row): string => SidikmaMasterData::normalized((string) $row->kelas_id));
        $schools = $pgsql->table('schools')->whereNull('deleted_at')->get();
        $schoolsByName = $schools->keyBy(fn ($row): string => SidikmaMasterData::normalized($row->name));
        $schoolsByNpsn = $schools->keyBy('npsn');
        $memberships = $pgsql->table('memberships')->get();
        $targetUsers = $pgsql->table('users')->pluck('id')->mapWithKeys(fn ($id): array => [(int) $id => true]);
        $rows = [];
        $stats = ['source' => $classes->count(), 'target' => $schools->count(), 'eligible' => 0, 'existing' => 0, 'conflict' => 0, 'invalid' => 0, 'membership_eligible' => 0, 'membership_existing' => 0];

        if ($foundation->count() !== 1) {
            $stats['conflict']++;

            return $stats + ['rows' => []];
        }
        $foundationId = (int) $foundation->first()->id;

        foreach ($classes as $class) {
            $oldId = (int) $class->id;
            $user = $users->get($oldId);
            $sip = $sipinter->get($oldId);
            $facility = $facilities->get(SidikmaMasterData::normalized($class->nama_kelas));
            $name = trim((string) $class->nama_kelas);
            $npsn = SidikmaSchoolData::validNpsn($sip?->npsn) ?? SidikmaSchoolData::validNpsn($user?->nis);
            $level = SidikmaSchoolData::schoolLevel($class->keterangan) ?? SidikmaSchoolData::schoolLevel($name);
            $existingByName = $schoolsByName->get(SidikmaMasterData::normalized($name));
            $existingByNpsn = $npsn ? $schoolsByNpsn->get($npsn) : null;
            $status = 'ELIGIBLE';
            $targetId = null;

            if (! $user || ! $targetUsers->has((int) $user->id) || ! $npsn || ! $level) {
                $status = 'INVALID';
                $stats['invalid']++;
            } elseif ($existingByName && $existingByNpsn && (int) $existingByName->id !== (int) $existingByNpsn->id) {
                $status = 'CONFLICT';
                $stats['conflict']++;
            } elseif ($existingByName || $existingByNpsn) {
                $existing = $existingByName ?? $existingByNpsn;
                if (SidikmaMasterData::normalized($existing->name) !== SidikmaMasterData::normalized($name) || $existing->npsn !== $npsn) {
                    $status = 'CONFLICT';
                    $stats['conflict']++;
                } else {
                    $status = 'EXISTING';
                    $targetId = (int) $existing->id;
                    $stats['existing']++;
                }
            } else {
                $stats['eligible']++;
            }

            $membershipExists = $targetId && $memberships->contains(fn ($membership): bool => (int) $membership->user_id === (int) $user->id && (int) $membership->school_id === $targetId && $membership->status === 'active');
            if ($status === 'EXISTING' && $membershipExists) {
                $stats['membership_existing']++;
            } elseif (in_array($status, ['ELIGIBLE', 'EXISTING'], true)) {
                $stats['membership_eligible']++;
            }

            $profile = [
                'foundation_id' => $foundationId,
                'name' => $name,
                'npsn' => $npsn,
                'school_level' => $level,
                'accreditation_status' => $this->nullable($facility?->status_akreditasi) ?? $this->nullable($user?->akreditasi),
                'accreditation_expiry_year' => SidikmaSchoolData::year($facility?->masa_akreditasi) ?? SidikmaSchoolData::year($user?->masaakreditasi),
                'address' => $this->nullable($sip?->alamat) ?? $this->nullable($user?->alamat),
                'land_status' => $this->nullable($facility?->status_tanah) ?? $this->nullable($user?->statustanah),
                'land_area' => SidikmaSchoolData::decimal($facility?->luas_tanah) ?? SidikmaSchoolData::decimal($user?->luastanah),
                'has_land_certificate' => SidikmaSchoolData::yesNo($facility?->kepemilikan_sertifikat) ?? SidikmaSchoolData::yesNo($user?->sertifikat),
                'has_bhpnu_ownership' => SidikmaSchoolData::yesNo($facility?->phbnu) ?? SidikmaSchoolData::yesNo($user?->phbnu),
                'phone' => $this->nullable($user?->no_tlp),
                'email' => $this->nullable($user?->email),
                'is_active' => $user?->status === 'ON',
            ];

            $rows[] = compact('oldId', 'targetId', 'status', 'profile') + ['admin_user_id' => (int) ($user?->id ?? 0), 'membership_exists' => (bool) $membershipExists];
        }

        return $stats + ['rows' => $rows];
    }

    /** @param array<string, mixed> $analysis */
    private function display(array $analysis, bool $dryRun): void
    {
        $this->info($dryRun ? 'DRY RUN MIGRASI SEKOLAH SIDIKMA' : 'MIGRASI SEKOLAH SIDIKMA');
        $this->table(['Pemeriksaan', 'Jumlah'], [
            ['Master sekolah SIDIKMA', $analysis['source']], ['Sekolah PostgreSQL', $analysis['target']], ['Sekolah eligible', $analysis['eligible']], ['Sekolah existing', $analysis['existing']], ['Konflik', $analysis['conflict']], ['Invalid', $analysis['invalid']], ['Membership eligible', $analysis['membership_eligible']], ['Membership existing', $analysis['membership_existing']],
        ]);
        $this->table(['ID Lama', 'Nama', 'NPSN', 'Level', 'ID Baru', 'Admin', 'Status'], array_map(fn (array $row): array => [$row['oldId'], $row['profile']['name'], $row['profile']['npsn'] ?? '-', $row['profile']['school_level'] ?? '-', $row['targetId'] ?? '-', $row['admin_user_id'], $row['status']], $analysis['rows']));
    }

    /** @param array<string, mixed> $analysis */
    private function migrate(array $analysis): void
    {
        DB::connection('pgsql')->transaction(function () use ($analysis): void {
            $now = now();
            foreach ($analysis['rows'] as $row) {
                $schoolId = $row['targetId'];
                if ($row['status'] === 'ELIGIBLE') {
                    $schoolId = DB::connection('pgsql')->table('schools')->insertGetId($row['profile'] + ['created_at' => $now, 'updated_at' => $now]);
                }
                if (! $row['membership_exists']) {
                    DB::connection('pgsql')->table('memberships')->insert(['user_id' => $row['admin_user_id'], 'foundation_id' => $row['profile']['foundation_id'], 'school_id' => $schoolId, 'status' => 'active', 'start_date' => null, 'end_date' => null, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        });
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
