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

        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('workdays_per_week')->default(5)->after('school_id');
            $table->time('friday_check_in_start')->nullable()->after('check_in_start');
            $table->time('friday_late_after')->nullable()->after('late_after');
            $table->time('friday_check_in_end')->nullable()->after('check_in_end');
            $table->time('friday_check_out_start')->nullable()->after('check_out_start');
            $table->time('friday_check_out_end')->nullable()->after('check_out_end');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance_settings')) {
            return;
        }

        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'workdays_per_week',
                'friday_check_in_start',
                'friday_late_after',
                'friday_check_in_end',
                'friday_check_out_start',
                'friday_check_out_end',
            ]);
        });
    }
};
