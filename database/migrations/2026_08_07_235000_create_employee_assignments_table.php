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
        Schema::create('employee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('employee_position_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('foundation_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('school_id')
                ->nullable()
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable()->index();
            $table->string('decree_number', 100)->nullable();
            $table->date('decree_date')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status', 'is_primary']);
            $table->index(['foundation_id', 'school_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_assignments');
    }
};
