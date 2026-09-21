<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decree_correction_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_number', 40)->unique();
            $table->date('request_date');
            $table->string('decree_number', 150);
            $table->date('decree_date');
            $table->string('subject_name');
            $table->string('correction_part', 50)->index();
            $table->text('old_data');
            $table->text('new_data');
            $table->text('reason');
            $table->string('old_decree_path');
            $table->string('supporting_document_path')->nullable();
            $table->string('corrected_decree_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->text('admin_notes')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['school_id', 'request_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decree_correction_requests');
    }
};
