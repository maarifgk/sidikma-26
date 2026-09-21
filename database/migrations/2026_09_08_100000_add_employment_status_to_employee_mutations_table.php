<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_mutations', function (Blueprint $table): void {
            $table->string('employment_status', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_mutations', function (Blueprint $table): void {
            $table->dropColumn('employment_status');
        });
    }
};
