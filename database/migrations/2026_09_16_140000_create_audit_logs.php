<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A failed MySQL FK operation can leave the table behind because DDL is
        // not transactional. Treat that partial result as successfully created.
        if (Schema::hasTable('audit_logs')) {
            $this->addMissingIndexes();

            return;
        }

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            // Audit records should survive deletion of their related records.
            // Plain indexed IDs also support legacy databases whose primary-key
            // definitions or storage engines cannot accept Laravel's FK shape.
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->unsignedBigInteger('target_user_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->unsignedBigInteger('school_id')->nullable();

            $table->string('action', 100);
            $table->string('entity_type', 150);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestampsTz();

            $table->index(['school_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }

    private function addMissingIndexes(): void
    {
        foreach ([
            ['actor_user_id'],
            ['target_user_id'],
            ['employee_id'],
            ['school_id', 'created_at'],
            ['entity_type', 'entity_id'],
        ] as $columns) {
            if (! Schema::hasIndex('audit_logs', $columns)) {
                Schema::table('audit_logs', fn (Blueprint $table) => $table->index($columns));
            }
        }
    }
};
