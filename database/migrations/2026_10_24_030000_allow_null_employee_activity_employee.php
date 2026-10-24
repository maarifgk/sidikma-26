<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_activity_requests') || ! Schema::hasColumn('employee_activity_requests', 'employee_id')) {
            return;
        }

        // Legacy rows may not have a matching employee in the new database.
        // Keep employee_name and legacy_snapshot as the authoritative fallback.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE employee_activity_requests MODIFY employee_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Do not make existing legacy rows invalid during rollback.
    }
};
