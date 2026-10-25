<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('foundations', 'payment_types_initialized')) {
            Schema::table('foundations', function (Blueprint $table): void {
                $table->boolean('payment_types_initialized')->default(false);
            });
        }

        if (! Schema::hasTable('payment_types')) {
            Schema::create('payment_types', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('foundation_id')->index();
                $table->string('name');
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->unique(['foundation_id', 'name']);
            });

            return;
        }

        $columns = [
            'foundation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('foundation_id')->nullable()->index(),
            'name' => fn (Blueprint $table) => $table->string('name')->nullable(),
            'is_active' => fn (Blueprint $table) => $table->boolean('is_active')->default(true)->index(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('payment_types', $column)) {
                Schema::table('payment_types', $definition);
            }
        }
    }

    public function down(): void
    {
        // Keep production payment-type data intact.
    }
};
