<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 20)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        } else {
            $columns = [
                'name' => fn (Blueprint $table) => $table->string('name', 20)->nullable(),
                'is_active' => fn (Blueprint $table) => $table->boolean('is_active')->default(true)->index(),
                'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
                'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
            ];

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('academic_years', $column)) {
                    Schema::table('academic_years', $definition);
                }
            }
        }

        if (Schema::hasTable('academic_years') && DB::table('academic_years')->count() === 0) {
            $now = now();
            DB::table('academic_years')->insert(collect(['2026/2027', '2025/2026', '2024/2025', '2023/2024'])
                ->map(fn (string $name): array => [
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
        }
    }

    public function down(): void
    {
        // Keep production academic-year data intact.
    }
};
