<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundation_annual_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->string('budget_year', 20);
            $table->string('work_program_report_path');
            $table->string('financial_report_path');
            $table->timestamps();

            $table->unique(['foundation_id', 'budget_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_annual_reports');
    }
};
