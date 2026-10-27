<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! Schema::hasColumn('users', 'is_active')) {
            return;
        }

        // The hosting database imported is_active as a generated column.
        // The application requires a normal writable boolean column.
        DB::statement('ALTER TABLE `users` MODIFY `is_active` TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        // Do not recreate the invalid generated-column definition.
    }
};
