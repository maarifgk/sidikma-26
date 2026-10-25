<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_records') && ! Schema::hasColumn('attendance_records', 'check_in_reason')) {
            Schema::table('attendance_records', function (Blueprint $table): void {
                $table->text('check_in_reason')->nullable()->after('check_in_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_records') && Schema::hasColumn('attendance_records', 'check_in_reason')) {
            Schema::table('attendance_records', fn (Blueprint $table) => $table->dropColumn('check_in_reason'));
        }
    }
};
