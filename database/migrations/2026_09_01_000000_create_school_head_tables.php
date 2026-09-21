<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_heads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('employee_id')->index()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->string('position', 100)->default('Kepala Madrasah/Sekolah');
            $table->date('started_at')->nullable()->index();
            $table->date('sk_start_date')->nullable()->index();
            $table->date('sk_end_date')->nullable()->index();
            $table->string('latest_sk_number', 180)->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'employee_id']);
            $table->index(['school_id', 'status', 'sk_end_date']);
        });

        Schema::create('school_head_job_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_head_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('started_at')->index();
            $table->date('ended_at')->nullable()->index();
            $table->string('position', 150);
            $table->string('institution_name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_head_certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_head_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('certificate_number', 180)->nullable()->index();
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_head_trainings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_head_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('activity_type', 30)->index();
            $table->string('organizer')->nullable();
            $table->date('started_at')->nullable()->index();
            $table->date('ended_at')->nullable()->index();
            $table->string('certificate_number', 180)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_head_achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_head_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('level', 30)->index();
            $table->date('achieved_at')->nullable()->index();
            $table->string('organizer')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_head_achievements');
        Schema::dropIfExists('school_head_trainings');
        Schema::dropIfExists('school_head_certificates');
        Schema::dropIfExists('school_head_job_histories');
        Schema::dropIfExists('school_heads');
    }
};
