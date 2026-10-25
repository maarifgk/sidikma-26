<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mutation_requirements')) {
            Schema::create('mutation_requirements', function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('position')->default(0)->index();
                $table->string('number', 20)->nullable();
                $table->text('description');
                $table->string('template_label', 100)->nullable();
                $table->string('template_path')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_mutations')) {
            Schema::create('employee_mutations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_mutation_id')->nullable()->unique();
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->string('employee_code', 50)->nullable();
                $table->string('employee_name')->nullable();
                $table->string('employment_status', 50)->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('birth_place', 100)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('mutation_type', 30)->index();
                $table->date('effective_date')->nullable();
                $table->unsignedBigInteger('origin_school_id')->nullable()->index();
                $table->string('origin_school_name')->nullable();
                $table->unsignedBigInteger('destination_school_id')->nullable()->index();
                $table->string('destination_school_name')->nullable();
                $table->string('request_letter_path');
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
            });
        }

        $columns = [
            'legacy_mutation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('legacy_mutation_id')->nullable()->unique(),
            'employee_id' => fn (Blueprint $table) => $table->unsignedBigInteger('employee_id')->nullable()->index(),
            'employee_code' => fn (Blueprint $table) => $table->string('employee_code', 50)->nullable(),
            'employee_name' => fn (Blueprint $table) => $table->string('employee_name')->nullable(),
            'employment_status' => fn (Blueprint $table) => $table->string('employment_status', 50)->nullable(),
            'phone' => fn (Blueprint $table) => $table->string('phone', 30)->nullable(),
            'birth_place' => fn (Blueprint $table) => $table->string('birth_place', 100)->nullable(),
            'birth_date' => fn (Blueprint $table) => $table->date('birth_date')->nullable(),
            'effective_date' => fn (Blueprint $table) => $table->date('effective_date')->nullable(),
            'origin_school_name' => fn (Blueprint $table) => $table->string('origin_school_name')->nullable(),
            'destination_school_name' => fn (Blueprint $table) => $table->string('destination_school_name')->nullable(),
            'legacy_snapshot' => fn (Blueprint $table) => $table->json('legacy_snapshot')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('employee_mutations', $column)) {
                Schema::table('employee_mutations', $definition);
            }
        }
    }

    public function down(): void
    {
        // Repair migrations do not remove production data on rollback.
    }
};
