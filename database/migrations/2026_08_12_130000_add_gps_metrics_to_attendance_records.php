<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->unsignedInteger('radius_meters')->default(100)->change();
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->decimal('check_in_accuracy', 10, 2)->nullable()->after('check_in_longitude');
            $table->decimal('check_in_distance', 10, 2)->nullable()->after('check_in_accuracy');
            $table->decimal('check_out_accuracy', 10, 2)->nullable()->after('check_out_longitude');
            $table->decimal('check_out_distance', 10, 2)->nullable()->after('check_out_accuracy');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->unsignedInteger('radius_meters')->default(200)->change();
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropColumn([
                'check_in_accuracy',
                'check_in_distance',
                'check_out_accuracy',
                'check_out_distance',
            ]);
        });
    }
};
