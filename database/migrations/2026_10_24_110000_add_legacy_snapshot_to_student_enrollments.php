<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_enrollments') && ! Schema::hasColumn('student_enrollments', 'legacy_snapshot')) {
            Schema::table('student_enrollments', function (Blueprint $table): void {
                $table->json('legacy_snapshot')->nullable();
            });
        }
    }

    public function down(): void {}
};
