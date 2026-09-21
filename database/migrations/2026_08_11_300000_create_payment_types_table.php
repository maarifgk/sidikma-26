<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['foundation_id', 'name']);
            $table->index(['foundation_id', 'is_active']);
        });

        Schema::table('foundations', function (Blueprint $table): void {
            $table->boolean('payment_types_initialized')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('foundations', function (Blueprint $table): void {
            $table->dropColumn('payment_types_initialized');
        });

        Schema::dropIfExists('payment_types');
    }
};
