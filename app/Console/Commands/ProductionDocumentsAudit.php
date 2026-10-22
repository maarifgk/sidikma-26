<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductionDocumentsAudit extends Command
{
    protected $signature = 'production:documents-audit {--connection= : Nama koneksi Laravel, default memakai koneksi aplikasi}';

    protected $description = 'Memeriksa path dokumen pada database dan keberadaan file pada storage secara read-only';

    public function handle(): int
    {
        $connectionName = $this->option('connection') ?: config('database.default');
        $db = DB::connection($connectionName);

        if (! Schema::connection($connectionName)->hasTable('documents')) {
            $this->error('FAIL  tabel documents tidak ditemukan');
            return self::FAILURE;
        }

        $checked = 0;
        $missing = 0;
        $empty = 0;
        $diskCounts = [];

        $db->table('documents')->select(['id', 'disk', 'path', 'size', 'checksum'])->orderBy('id')->chunkById(250, function ($documents) use (&$checked, &$missing, &$empty, &$diskCounts): void {
            foreach ($documents as $document) {
                $checked++;
                $diskName = $document->disk ?: config('filesystems.default');
                $diskCounts[$diskName] = ($diskCounts[$diskName] ?? 0) + 1;
                if (! $document->path) {
                    $empty++;
                    $this->warn("WARNING document #{$document->id}: path kosong");
                    continue;
                }
                try {
                    $exists = Storage::disk($diskName)->exists($document->path);
                } catch (\Throwable $e) {
                    $exists = false;
                    $this->warn("WARNING document #{$document->id}: disk {$diskName} gagal diakses ({$e->getMessage()})");
                }
                if (! $exists) {
                    $missing++;
                    $this->warn("WARNING document #{$document->id}: file tidak ditemukan {$document->path}");
                }
            }
        });

        $this->info("Checked: {$checked}");
        $this->info('Disk: ' . json_encode($diskCounts, JSON_UNESCAPED_SLASHES));
        $missing === 0 ? $this->info('PASS  seluruh path memiliki file') : $this->warn("WARNING missing files: {$missing}");
        $empty === 0 ? $this->info('PASS  tidak ada path kosong') : $this->warn("WARNING empty paths: {$empty}");

        return self::SUCCESS;
    }
}
