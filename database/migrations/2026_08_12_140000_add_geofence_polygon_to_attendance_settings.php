<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->json('geofence_polygon')->nullable()->after('radius_meters');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', fn (Blueprint $table) => $table->dropColumn('geofence_polygon'));
    }
};
