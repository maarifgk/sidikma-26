<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('approval_requests')) {
            Schema::create('approval_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('approvable_type');
                $table->unsignedBigInteger('approvable_id');
                $table->string('status', 20)->default('draft')->index();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->unsignedBigInteger('verified_by')->nullable()->index();
                $table->unsignedBigInteger('decided_by')->nullable()->index();
                $table->timestamp('submitted_at')->nullable()->index();
                $table->timestamp('verified_at')->nullable()->index();
                $table->timestamp('decided_at')->nullable()->index();
                $table->text('submission_notes')->nullable();
                $table->text('verification_notes')->nullable();
                $table->text('decision_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['approvable_type', 'approvable_id']);
                $table->index(['approvable_type', 'status']);
            });
        }
    }

    public function down(): void
    {
        // Do not remove production approval data on rollback.
    }
};
