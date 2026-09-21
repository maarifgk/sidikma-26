<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('last_education', 100)->nullable()->after('employment_status');
            $table->string('program_study', 150)->nullable()->after('last_education');
        });

        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->string('decree_period', 20)->nullable()->after('decree_date')->index();
        });
    }

    public function down(): void
    {
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropColumn('decree_period');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['last_education', 'program_study']);
        });
    }
};
