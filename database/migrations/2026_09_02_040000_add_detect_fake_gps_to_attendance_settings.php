<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->boolean('detect_fake_gps')->default(false)->after('require_location');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->dropColumn('detect_fake_gps');
        });
    }
};
