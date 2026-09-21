<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateSidikmaLegacyMetadata extends Command
{
    protected $signature = 'sidikma:migrate-legacy-metadata {--dry-run} {--force}';
    protected $description = 'Arsipkan metadata SIDIKMA yang tidak memiliki struktur setara tanpa menyimpan rahasia';

    public function handle(): int
    {
        $m = DB::connection('mysql_sidikma');
        $p = DB::connection('pgsql');
        $tables = ['profile_lembaga', 'sk_templates', 'sk_yayasan_settings', 'sk_yayasan_documents', 'sk', 'broadcasts'];
        $rows = [];
        foreach ($tables as $table) {
            $existing = $p->table('legacy_migration_records')->where('source_table', $table)->pluck('source_id')->flip();
            foreach ($m->table($table)->orderBy('id')->get() as $row) {
                if (! $existing->has((int) $row->id)) $rows[] = $this->record($table, $row);
            }
        }
        $appExisting = $p->table('legacy_migration_records')->where('source_table', 'aplikasi')->pluck('source_id')->flip();
        foreach ($m->table('aplikasi')->orderBy('id')->get() as $row) {
            if ($appExisting->has((int) $row->id)) continue;
            $safe = (array) $row;
            unset($safe['token_whatsapp'], $safe['serverKey'], $safe['clientKey']);
            $rows[] = $this->record('aplikasi', (object) $safe, 'Token dan key rahasia sengaja tidak disalin.');
        }
        $counts = collect($rows)->countBy('source_table');
        $this->table(['Tabel sumber', 'Baru'], $counts->map(fn ($count, $table) => [$table, $count])->values()->all());
        if ($this->option('dry-run')) { $this->info('DRY RUN SELESAI — PostgreSQL tidak diubah.'); return self::SUCCESS; }
        if (! $this->option('force') && ! $this->confirm('Arsipkan metadata legacy yang belum ada?')) return self::SUCCESS;
        try {
            $p->transaction(function () use ($p, $rows): void {
                foreach (array_chunk($rows, 100) as $chunk) if ($chunk) $p->table('legacy_migration_records')->insert($chunk);
                $profile = DB::connection('mysql_sidikma')->table('profile_lembaga')->orderBy('id')->first();
                if ($profile) {
                    $foundation = $p->table('foundations')->whereNull('deleted_at')->orderBy('id')->first();
                    if ($foundation) $p->table('foundations')->where('id', $foundation->id)->update([
                        'address' => $foundation->address ?: trim((string) $profile->alamat),
                        'phone' => $foundation->phone ?: trim((string) $profile->tlp),
                        'updated_at' => now(),
                    ]);
                }
            });
        } catch (Throwable $e) {
            report($e); $this->error('Gagal; transaksi di-rollback: '.$e->getMessage()); return self::FAILURE;
        }
        $this->info('MIGRASI METADATA LEGACY SELESAI — '.count($rows).' record.'); return self::SUCCESS;
    }

    private function record(string $table, object $row, ?string $note = null): array
    {
        return ['source_table' => $table, 'source_id' => (int) $row->id, 'payload' => json_encode((array) $row, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), 'migration_note' => $note, 'created_at' => now(), 'updated_at' => now()];
    }
}
