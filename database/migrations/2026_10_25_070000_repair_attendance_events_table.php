<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_events')) {
            Schema::create('attendance_events', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('attendance_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->date('attendance_date')->index();
                $table->string('event_type', 20);
                $table->string('event_status', 20)->default('accepted')->index();
                $table->timestamp('checked_at')->nullable();
                $table->string('rejection_code', 64)->nullable()->index();
                $table->text('rejection_reason')->nullable();
                $table->boolean('is_mock_location')->nullable();
                $table->string('mock_detection_source')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('gps_accuracy', 10, 2)->nullable();
                $table->boolean('is_inside_geofence')->nullable();
                $table->string('selfie_path')->nullable();
                $table->string('device_info')->nullable();
                $table->unsignedBigInteger('geofence_version')->nullable();
                $table->unsignedBigInteger('client_geofence_version')->nullable();
                $table->timestamps();
            });

            return;
        }

        $columns = [
            'attendance_id' => fn (Blueprint $table) => $table->unsignedBigInteger('attendance_id')->nullable()->index(),
            'user_id' => fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable()->index(),
            'employee_id' => fn (Blueprint $table) => $table->unsignedBigInteger('employee_id')->nullable()->index(),
            'school_id' => fn (Blueprint $table) => $table->unsignedBigInteger('school_id')->nullable()->index(),
            'attendance_date' => fn (Blueprint $table) => $table->date('attendance_date')->nullable()->index(),
            'event_type' => fn (Blueprint $table) => $table->string('event_type', 20)->nullable(),
            'event_status' => fn (Blueprint $table) => $table->string('event_status', 20)->default('accepted')->index(),
            'checked_at' => fn (Blueprint $table) => $table->timestamp('checked_at')->nullable(),
            'rejection_code' => fn (Blueprint $table) => $table->string('rejection_code', 64)->nullable()->index(),
            'rejection_reason' => fn (Blueprint $table) => $table->text('rejection_reason')->nullable(),
            'is_mock_location' => fn (Blueprint $table) => $table->boolean('is_mock_location')->nullable(),
            'mock_detection_source' => fn (Blueprint $table) => $table->string('mock_detection_source')->nullable(),
            'latitude' => fn (Blueprint $table) => $table->decimal('latitude', 10, 7)->nullable(),
            'longitude' => fn (Blueprint $table) => $table->decimal('longitude', 10, 7)->nullable(),
            'gps_accuracy' => fn (Blueprint $table) => $table->decimal('gps_accuracy', 10, 2)->nullable(),
            'is_inside_geofence' => fn (Blueprint $table) => $table->boolean('is_inside_geofence')->nullable(),
            'selfie_path' => fn (Blueprint $table) => $table->string('selfie_path')->nullable(),
            'device_info' => fn (Blueprint $table) => $table->string('device_info')->nullable(),
            'geofence_version' => fn (Blueprint $table) => $table->unsignedBigInteger('geofence_version')->nullable(),
            'client_geofence_version' => fn (Blueprint $table) => $table->unsignedBigInteger('client_geofence_version')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('attendance_events', $column)) {
                Schema::table('attendance_events', $definition);
            }
        }
    }

    public function down(): void
    {
        // Keep attendance audit history intact.
    }
};
