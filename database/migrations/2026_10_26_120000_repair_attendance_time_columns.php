<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_settings')) {
            return;
        }

        if (! Schema::hasColumn('attendance_settings', 'check_in_end')) {
            Schema::table('attendance_settings', function (Blueprint $table): void {
                $table->time('check_in_end')->nullable()->after('late_after');
            });
        }

        if (! Schema::hasColumn('attendance_settings', 'check_out_end')) {
            Schema::table('attendance_settings', function (Blueprint $table): void {
                $table->time('check_out_end')->default('17:00:00')->after('check_out_start');
            });
        }
    }

    public function down(): void
    {
        // Repair migration: keep existing production data intact on rollback.
    }
};
