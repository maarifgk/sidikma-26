<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 9)->index();
            $table->unsignedInteger('k1')->default(0);
            $table->unsignedInteger('k2')->default(0);
            $table->unsignedInteger('k3')->default(0);
            $table->unsignedInteger('k4')->default(0);
            $table->unsignedInteger('k5')->default(0);
            $table->unsignedInteger('k6')->default(0);
            $table->unsignedInteger('k7')->default(0);
            $table->unsignedInteger('k8')->default(0);
            $table->unsignedInteger('k9')->default(0);
            $table->unsignedInteger('total')->default(0)->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
