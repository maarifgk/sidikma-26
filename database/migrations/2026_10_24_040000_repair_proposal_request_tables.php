<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proposal_requirements')) {
            Schema::create('proposal_requirements', function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('position')->default(0)->index();
                $table->string('number', 20)->nullable();
                $table->text('description');
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('proposal_requests')) {
            Schema::create('proposal_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_proposal_id')->nullable()->unique();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('school_name');
                $table->string('proposal_type');
                $table->string('request_file_path');
                $table->decimal('requested_amount', 15, 2)->default(0);
                $table->string('bank_name');
                $table->string('bank_account_number', 100);
                $table->string('bank_account_name')->nullable();
                $table->text('description')->nullable();
                $table->string('process_status', 30)->default('submitted')->index();
                $table->string('approval_file_path')->nullable();
                $table->decimal('approved_amount', 15, 2)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->unsignedBigInteger('processed_by')->nullable()->index();
                $table->timestamp('processed_at')->nullable();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
            });
        }

        foreach ([
            'legacy_proposal_id' => fn (Blueprint $table) => $table->unsignedBigInteger('legacy_proposal_id')->nullable()->unique(),
            'bank_account_name' => fn (Blueprint $table) => $table->string('bank_account_name')->nullable(),
            'legacy_snapshot' => fn (Blueprint $table) => $table->json('legacy_snapshot')->nullable(),
        ] as $column => $definition) {
            if (! Schema::hasColumn('proposal_requests', $column)) {
                Schema::table('proposal_requests', $definition);
            }
        }
    }

    public function down(): void
    {
        // Do not remove production data on rollback.
    }
};
