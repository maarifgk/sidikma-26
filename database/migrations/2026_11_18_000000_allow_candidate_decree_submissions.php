<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decree_submissions', function (Blueprint $table): void {
            $table->dropForeign(['employee_id']);
            $table->foreignId('employee_id')->nullable()->change();
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->string('candidate_name')->nullable()->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('decree_submissions', function (Blueprint $table): void {
            $table->dropForeign(['employee_id']);
            $table->foreignId('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->dropColumn('candidate_name');
        });
    }
};
