<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_mutations', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_mutation_id')->nullable()->unique()->after('id');
            $table->json('legacy_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_mutations', function (Blueprint $table): void {
            $table->dropColumn(['legacy_mutation_id', 'legacy_snapshot']);
        });
    }
};
