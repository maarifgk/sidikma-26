<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('correspondence_types')) {
            Schema::create('correspondence_types', function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('position')->default(0)->index();
                $table->string('number', 20)->nullable();
                $table->text('name');
                $table->boolean('is_selectable')->default(true)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('correspondence_requests')) {
            Schema::create('correspondence_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_correspondence_id')->nullable()->unique();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('school_name');
                $table->unsignedBigInteger('correspondence_type_id')->nullable()->index();
                $table->string('type_name');
                $table->string('request_file_path');
                $table->string('response_file_path')->nullable();
                $table->string('process_status', 30)->default('submitted')->index();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->unsignedBigInteger('processed_by')->nullable()->index();
                $table->timestamp('processed_at')->nullable();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
            });
        }

        $columns = [
            'legacy_correspondence_id' => fn (Blueprint $table) => $table->unsignedBigInteger('legacy_correspondence_id')->nullable()->unique(),
            'legacy_snapshot' => fn (Blueprint $table) => $table->json('legacy_snapshot')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('correspondence_requests', $column)) {
                Schema::table('correspondence_requests', $definition);
            }
        }
    }

    public function down(): void
    {
        // Do not remove production data on rollback.
    }
};
