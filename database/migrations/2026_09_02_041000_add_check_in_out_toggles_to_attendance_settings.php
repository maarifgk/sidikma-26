<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->boolean('enable_check_in')->default(true)->after('is_active');
            $table->boolean('enable_check_out')->default(true)->after('enable_check_in');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->dropColumn(['enable_check_in', 'enable_check_out']);
        });
    }
};
