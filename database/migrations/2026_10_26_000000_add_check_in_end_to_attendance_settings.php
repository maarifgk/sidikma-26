<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_settings') && ! Schema::hasColumn('attendance_settings', 'check_in_end')) {
            Schema::table('attendance_settings', function (Blueprint $table): void {
                $table->time('check_in_end')->nullable()->after('late_after');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_settings') && Schema::hasColumn('attendance_settings', 'check_in_end')) {
            Schema::table('attendance_settings', fn (Blueprint $table) => $table->dropColumn('check_in_end'));
        }
    }
};
