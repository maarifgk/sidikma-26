<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_settings')) {
            Schema::create('attendance_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->time('check_in_start')->default('06:00:00');
            $table->time('late_after')->default('07:15:00');
            $table->time('check_out_start')->default('14:00:00');
            $table->decimal('office_latitude', 10, 7)->nullable();
            $table->decimal('office_longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_meters')->default(200);
            $table->boolean('require_location')->default(false);
            $table->boolean('require_selfie')->default(false);
            $table->boolean('is_active')->default(true);
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index('updated_by');
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('user_id');
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status', 20)->index();
            $table->timestampTz('check_in_at')->nullable();
            $table->timestampTz('check_out_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->string('check_in_selfie_path')->nullable();
            $table->string('check_out_selfie_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
            $table->index(['school_id', 'attendance_date']);
            $table->index(['user_id', 'attendance_date']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('attendance_leave_requests')) {
            Schema::create('attendance_leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('user_id');
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('review_notes')->nullable();
            $table->bigInteger('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'start_date', 'end_date']);
            $table->index(['employee_id', 'status']);
            $table->index('user_id');
            $table->index('reviewed_by');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_leave_requests');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_settings');
    }
};
