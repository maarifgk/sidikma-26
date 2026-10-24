<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_enrollments')) {
            Schema::create('student_enrollments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('academic_year', 9)->index();
                foreach (range(1, 9) as $grade) {
                    $table->unsignedInteger('k'.$grade)->default(0);
                }
                $table->unsignedInteger('total')->default(0)->index();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
                $table->unique(['school_id', 'academic_year']);
            });
        }
    }

    public function down(): void {}
};
