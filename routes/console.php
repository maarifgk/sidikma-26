<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:production-check', function (): int {
    $checks = [
        'APP_ENV harus production' => app()->environment('production'),
        'APP_DEBUG harus false' => ! config('app.debug'),
        'APP_URL harus HTTPS' => str_starts_with((string) config('app.url'), 'https://'),
        'Session harus terenkripsi' => (bool) config('session.encrypt'),
        'Cookie session harus secure' => (bool) config('session.secure'),
        'Cookie session harus HTTP-only' => (bool) config('session.http_only'),
        'Database dapat diakses' => Schema::hasTable('users'),
        'APP_KEY tersedia' => filled(config('app.key')),
    ];

    foreach ($checks as $label => $passed) {
        $passed ? $this->info("[OK] {$label}") : $this->error("[GAGAL] {$label}");
    }

    if (in_array(false, $checks, true)) {
        $this->newLine();
        $this->warn('Aplikasi belum siap dijalankan sebagai production.');

        return self::FAILURE;
    }

    $this->newLine();
    $this->info('Konfigurasi dasar production sudah siap.');

    return self::SUCCESS;
})->purpose('Memeriksa kesiapan konfigurasi dasar sebelum deployment production');
