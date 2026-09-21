<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Support\SidikmaMasterData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateSidikmaMasterData extends Command
{
    protected $signature = 'sidikma:migrate-master-data
                            {--dry-run : Analisis tanpa mengubah PostgreSQL}
                            {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Migrasi master data SIDIKMA ke PostgreSQL';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $analysis = $this->analyze();

        $this->displayAnalysis($analysis, $dryRun);

        if ($analysis['totals']['invalid'] > 0 || $analysis['totals']['conflict'] > 0) {
            $this->error('Migrasi diblokir karena masih ada data invalid atau konflik.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('DRY RUN SELESAI — PostgreSQL tidak diubah.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Migrasikan {$analysis['totals']['eligible']} master data ke PostgreSQL?",
        )) {
            $this->warn('Migrasi dibatalkan. PostgreSQL tidak diubah.');

            return self::SUCCESS;
        }

        try {
            $this->migrate($analysis);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Migrasi gagal dan seluruh perubahan telah di-rollback.');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $validation = $this->analyze();

        if ($validation['totals']['eligible'] !== 0 || $validation['totals']['invalid'] !== 0 || $validation['totals']['conflict'] !== 0) {
            $this->error('Validasi pascamigrasi gagal. Jalankan kembali --dry-run untuk detail.');

            return self::FAILURE;
        }

        $this->info("MIGRASI SELESAI — {$analysis['totals']['eligible']} master data dibuat.");

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function analyze(): array
    {
        $datasets = [
            'Tahun ajaran' => $this->analyzeAcademicYears(),
            'Ketugasan/posisi' => $this->analyzeEmployeePositions(),
            'Jenis pembayaran umum' => $this->analyzePaymentTypes(),
            'Jenis surat' => $this->analyzeCorrespondenceTypes(),
        ];

        $totals = [
            'source' => array_sum(array_column($datasets, 'source')),
            'target' => array_sum(array_column($datasets, 'target')),
            'eligible' => array_sum(array_column($datasets, 'eligible')),
            'existing' => array_sum(array_column($datasets, 'existing')),
            'conflict' => array_sum(array_column($datasets, 'conflict')),
            'invalid' => array_sum(array_column($datasets, 'invalid')),
            'skipped' => array_sum(array_column($datasets, 'skipped')),
        ];

        return compact('datasets', 'totals');
    }

    /** @return array<string, mixed> */
    private function analyzeAcademicYears(): array
    {
        $source = DB::connection('mysql_sidikma')->table('tahun_ajaran')->orderBy('id')->get();
        $target = DB::connection('pgsql')->table('academic_years')->get()->keyBy('name');
        $rows = [];
        $stats = $this->emptyStats($source->count(), $target->count());

        foreach ($source as $item) {
            $name = trim((string) $item->tahun);
            $status = 'ELIGIBLE';
            $targetId = null;

            if (! AcademicYear::isValidPeriod($name)) {
                $status = 'INVALID';
                $stats['invalid']++;
            } elseif ($target->has($name)) {
                $status = 'EXISTING';
                $targetId = (int) $target[$name]->id;
                $stats['existing']++;
            } else {
                $stats['eligible']++;
            }

            $rows[] = ['source_id' => (int) $item->id, 'source' => $name, 'target' => $name, 'target_id' => $targetId, 'status' => $status];
        }

        return $stats + ['rows' => $rows];
    }

    /** @return array<string, mixed> */
    private function analyzeEmployeePositions(): array
    {
        $source = DB::connection('mysql_sidikma')->table('ketugasan')->orderBy('id')->get();
        $target = DB::connection('pgsql')->table('employee_positions')->whereNull('deleted_at')->get()->keyBy('code');
        $definitions = SidikmaMasterData::employeePositionsByName();
        $rows = [];
        $stats = $this->emptyStats($source->count(), $target->count());

        foreach ($source as $item) {
            $name = trim((string) $item->ketugasan);
            $definition = $definitions[SidikmaMasterData::normalized($name)] ?? null;
            $status = 'ELIGIBLE';
            $targetId = null;
            $targetName = $definition['name'] ?? '-';

            if ($definition === null) {
                $status = 'INVALID';
                $stats['invalid']++;
            } elseif ($target->has($definition['code'])) {
                $existing = $target[$definition['code']];
                $targetId = (int) $existing->id;
                if (SidikmaMasterData::normalized($existing->name) !== SidikmaMasterData::normalized($definition['name'])) {
                    $status = 'CONFLICT';
                    $stats['conflict']++;
                } else {
                    $status = 'EXISTING';
                    $stats['existing']++;
                }
            } else {
                $stats['eligible']++;
            }

            $rows[] = ['source_id' => (int) $item->id, 'source' => $name, 'target' => $targetName, 'target_id' => $targetId, 'status' => $status, 'payload' => $definition];
        }

        return $stats + ['rows' => $rows];
    }

    /** @return array<string, mixed> */
    private function analyzePaymentTypes(): array
    {
        $foundation = DB::connection('pgsql')->table('foundations')->whereNull('deleted_at')->get();
        $source = DB::connection('mysql_sidikma')->table('jenis_pembayaran')->orderBy('id')->get();
        $target = DB::connection('pgsql')->table('payment_types')->get();
        $stats = $this->emptyStats($source->count(), $target->count());
        $rows = [];

        if ($foundation->count() !== 1) {
            $stats['conflict']++;

            return $stats + ['rows' => [['source_id' => '-', 'source' => 'Foundation aktif', 'target' => 'Harus tepat satu', 'target_id' => null, 'status' => 'CONFLICT']]];
        }

        $foundationId = (int) $foundation->first()->id;
        $targetByName = $target->where('foundation_id', $foundationId)
            ->keyBy(fn ($item): string => SidikmaMasterData::normalized($item->name));
        $grouped = $source->groupBy(fn ($item): string => SidikmaMasterData::generalPaymentType($item->pembayaran) ?? '');

        foreach ($source as $item) {
            $generalName = SidikmaMasterData::generalPaymentType((string) $item->pembayaran);
            if ($generalName === null) {
                $stats['invalid']++;
                $rows[] = ['source_id' => (int) $item->id, 'source' => $item->pembayaran, 'target' => '-', 'target_id' => null, 'status' => 'INVALID'];
            }
        }

        foreach ($grouped->except('') as $generalName => $items) {
            $key = SidikmaMasterData::normalized($generalName);
            $existing = $targetByName->get($key);
            $status = $existing ? 'EXISTING' : 'ELIGIBLE';
            $existing ? $stats['existing']++ : $stats['eligible']++;
            $stats['skipped'] += $items->count() - 1;

            $rows[] = [
                'source_id' => $items->pluck('id')->implode(','),
                'source' => $items->count().' variasi legacy',
                'target' => $generalName,
                'target_id' => $existing ? (int) $existing->id : null,
                'status' => $status,
                'payload' => ['foundation_id' => $foundationId, 'name' => $generalName, 'is_active' => $items->contains(fn ($item): bool => $item->status === 'ON')],
            ];
        }

        return $stats + ['rows' => $rows];
    }

    /** @return array<string, mixed> */
    private function analyzeCorrespondenceTypes(): array
    {
        $source = DB::connection('mysql_sidikma')->table('jenis_surat')->orderBy('id')->get();
        $target = DB::connection('pgsql')->table('correspondence_types')->get();
        $targetByName = $target->keyBy(fn ($item): string => SidikmaMasterData::normalized($item->name));
        $stats = $this->emptyStats($source->count(), $target->count());
        $rows = [];
        $nextPosition = ((int) $target->max('position')) + 1;

        foreach ($source as $item) {
            $name = trim((string) $item->nama_jenis);
            $existing = $targetByName->get(SidikmaMasterData::normalized($name));

            if ($name === '') {
                $status = 'INVALID';
                $stats['invalid']++;
            } elseif ($existing) {
                $status = 'EXISTING';
                $stats['existing']++;
            } else {
                $status = 'ELIGIBLE';
                $stats['eligible']++;
            }

            $rows[] = [
                'source_id' => (int) $item->id,
                'source' => $name,
                'target' => $name,
                'target_id' => $existing ? (int) $existing->id : null,
                'status' => $status,
                'payload' => ['position' => $existing?->position ?? $nextPosition++, 'number' => null, 'name' => $name, 'is_selectable' => true, 'is_active' => true],
            ];
        }

        return $stats + ['rows' => $rows];
    }

    /** @return array{source: int, target: int, eligible: int, existing: int, conflict: int, invalid: int, skipped: int} */
    private function emptyStats(int $source, int $target): array
    {
        return compact('source', 'target') + ['eligible' => 0, 'existing' => 0, 'conflict' => 0, 'invalid' => 0, 'skipped' => 0];
    }

    /** @param array<string, mixed> $analysis */
    private function displayAnalysis(array $analysis, bool $dryRun): void
    {
        $this->info($dryRun ? 'DRY RUN MIGRASI MASTER DATA SIDIKMA' : 'MIGRASI MASTER DATA SIDIKMA');
        $this->table(
            ['Dataset', 'Source', 'Target', 'Eligible', 'Existing', 'Conflict', 'Invalid', 'Skipped'],
            collect($analysis['datasets'])->map(fn (array $data, string $name): array => [
                $name, $data['source'], $data['target'], $data['eligible'], $data['existing'], $data['conflict'], $data['invalid'], $data['skipped'],
            ])->values()->all(),
        );

        foreach ($analysis['datasets'] as $name => $dataset) {
            $this->newLine();
            $this->info(strtoupper($name));
            $this->table(['ID Source', 'Source', 'Target', 'ID Target', 'Status'], array_map(
                fn (array $row): array => [$row['source_id'], $row['source'], $row['target'], $row['target_id'] ?? '-', $row['status']],
                $dataset['rows'],
            ));
        }
    }

    /** @param array<string, mixed> $analysis */
    private function migrate(array $analysis): void
    {
        DB::connection('pgsql')->transaction(function () use ($analysis): void {
            $now = now();
            foreach ($analysis['datasets']['Tahun ajaran']['rows'] as $row) {
                if ($row['status'] === 'ELIGIBLE') {
                    DB::connection('pgsql')->table('academic_years')->insert(['name' => $row['target'], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
            foreach ($analysis['datasets']['Ketugasan/posisi']['rows'] as $row) {
                if ($row['status'] === 'ELIGIBLE') {
                    DB::connection('pgsql')->table('employee_positions')->insert($row['payload'] + ['description' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
            foreach ($analysis['datasets']['Jenis pembayaran umum']['rows'] as $row) {
                if ($row['status'] === 'ELIGIBLE') {
                    DB::connection('pgsql')->table('payment_types')->insert($row['payload'] + ['created_at' => $now, 'updated_at' => $now]);
                }
            }
            foreach ($analysis['datasets']['Jenis surat']['rows'] as $row) {
                if ($row['status'] === 'ELIGIBLE') {
                    DB::connection('pgsql')->table('correspondence_types')->insert($row['payload'] + ['created_by' => null, 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        });
    }
}
