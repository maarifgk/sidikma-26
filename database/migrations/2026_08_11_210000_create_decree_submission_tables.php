<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('decree_submission_types')) {
            Schema::create('decree_submission_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('decree_submissions')) {
            Schema::create('decree_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('submission_number', 50)->unique();
            $table->date('submission_date')->index();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('decree_submission_type_id')->constrained()->restrictOnDelete();
            $table->text('purpose')->nullable();
            $table->string('status', 30)->default('under_review')->index();
            $table->text('admin_notes')->nullable();
            $table->string('result_file_path')->nullable();
            $table->string('result_original_name')->nullable();
            $table->string('result_mime_type', 100)->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status', 'submission_date'], 'decree_submissions_school_status_date');
            });
        }

        if (! Schema::hasTable('decree_submission_status_histories')) {
            Schema::create('decree_submission_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('decree_submission_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['decree_submission_id', 'created_at'], 'decree_submission_history_time');
            });
        }

        DB::table('decree_submission_types')->insertOrIgnore([
            ['code' => 'SK-PENGANGKATAN', 'name' => 'SK Pengangkatan', 'description' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SK-PERPANJANGAN', 'name' => 'SK Perpanjangan', 'description' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SK-PENUGASAN', 'name' => 'SK Penugasan', 'description' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('decree_submission_status_histories');
        Schema::dropIfExists('decree_submissions');
        Schema::dropIfExists('decree_submission_types');
    }
};
