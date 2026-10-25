<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_activity_requests')) {
            Schema::create('employee_activity_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_activity_id')->nullable()->unique();
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->string('employee_name');
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('school_name')->nullable();
                $table->date('inactive_date')->nullable()->index();
                $table->string('request_letter_path');
                $table->string('status', 30)->default('submitted')->index();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->unsignedBigInteger('processed_by')->nullable()->index();
                $table->timestamp('processed_at')->nullable();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
            });
        }

        foreach ([
            'legacy_activity_id' => fn (Blueprint $table) => $table->unsignedBigInteger('legacy_activity_id')->nullable()->unique(),
            'legacy_snapshot' => fn (Blueprint $table) => $table->json('legacy_snapshot')->nullable(),
        ] as $column => $definition) {
            if (! Schema::hasColumn('employee_activity_requests', $column)) {
                Schema::table('employee_activity_requests', $definition);
            }
        }
    }

    public function down(): void
    {
        // Do not remove production data on rollback.
    }
};
