<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionDatabaseHealth extends Command
{
    protected $signature = 'production:database-health {--connection= : Nama koneksi Laravel, default memakai koneksi aplikasi}';

    protected $description = 'Memeriksa koneksi, tabel inti, jumlah data, orphan, dan duplikasi database secara read-only';

    public function handle(): int
    {
        $connectionName = $this->option('connection') ?: config('database.default');
        $db = DB::connection($connectionName);
        $schema = $db->getSchemaBuilder();

        $this->info('Database health check (read-only)');
        $this->line('Connection: ' . $connectionName);
        $this->line('Driver: ' . $db->getDriverName());
        $this->line('Database: ' . ($db->getDatabaseName() ?: '-'));

        try {
            $db->select('select 1');
            $this->info('PASS  koneksi database');
        } catch (\Throwable $e) {
            $this->error('FAIL  koneksi database: ' . $e->getMessage());
            return self::FAILURE;
        }

        $tables = [
            'users', 'migrations', 'roles', 'permissions', 'foundations', 'schools',
            'memberships', 'employees', 'employee_assignments', 'documents',
            'payment_invoices', 'payment_transactions', 'attendance_records',
        ];

        foreach ($tables as $table) {
            if (! $schema->hasTable($table)) {
                $this->warn("WARNING {$table}: tabel tidak ditemukan");
                continue;
            }

            try {
                $this->line(sprintf('PASS  %-24s %d row(s)', $table, $db->table($table)->count()));
            } catch (\Throwable $e) {
                $this->warn("WARNING {$table}: {$e->getMessage()}");
            }
        }

        $this->checkOrphan($db, $schema, 'memberships', 'user_id', 'users', 'id');
        $this->checkOrphan($db, $schema, 'memberships', 'foundation_id', 'foundations', 'id');
        $this->checkOrphan($db, $schema, 'memberships', 'school_id', 'schools', 'id');
        $this->checkOrphan($db, $schema, 'employees', 'user_id', 'users', 'id');
        $this->checkOrphan($db, $schema, 'employee_assignments', 'employee_id', 'employees', 'id');

        foreach ([['schools', 'npsn'], ['employees', 'employee_code'], ['employees', 'email'], ['employees', 'nik'], ['employees', 'nip'], ['employees', 'nuptk']] as [$table, $column]) {
            if (! $schema->hasTable($table) || ! $this->hasColumn($schema, $table, $column)) {
                continue;
            }
            $duplicates = $db->table($table)
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->select($column)
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->count();
            $duplicates === 0
                ? $this->info("PASS  duplicate {$table}.{$column}")
                : $this->warn("WARNING duplicate {$table}.{$column}: {$duplicates} value(s)");
        }

        return self::SUCCESS;
    }

    private function hasColumn($schema, string $table, string $column): bool
    {
        try {
            return $schema->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkOrphan(Connection $db, $schema, string $child, string $foreignKey, string $parent, string $parentKey): void
    {
        if (! $schema->hasTable($child) || ! $schema->hasTable($parent) || ! $this->hasColumn($schema, $child, $foreignKey)) {
            return;
        }

        $query = $db->table($child . ' as c')
            ->leftJoin($parent . ' as p', 'p.' . $parentKey, '=', 'c.' . $foreignKey)
            ->whereNotNull('c.' . $foreignKey)
            ->whereNull('p.' . $parentKey);
        $count = $query->count();
        $label = "orphan {$child}.{$foreignKey} -> {$parent}.{$parentKey}";
        $count === 0 ? $this->info("PASS  {$label}") : $this->warn("WARNING {$label}: {$count} row(s)");
    }
}
