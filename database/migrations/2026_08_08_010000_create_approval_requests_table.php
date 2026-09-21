<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('submitted_by')
                ->nullable()
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('verified_by')
                ->nullable()
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('decided_by')
                ->nullable()
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('verified_at')->nullable()->index();
            $table->timestamp('decided_at')->nullable()->index();
            $table->text('submission_notes')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['approvable_type', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
