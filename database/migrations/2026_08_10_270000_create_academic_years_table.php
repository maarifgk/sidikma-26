<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 20)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();

        DB::table('academic_years')->insert(collect([
            '2026/2027',
            '2025/2026',
            '2024/2025',
            '2023/2024',
        ])->map(fn (string $name): array => [
            'name' => $name,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
