<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('foundations', 'payment_fees_initialized')) {
            Schema::table('foundations', function (Blueprint $table): void {
                $table->boolean('payment_fees_initialized')->default(false);
            });
        }

        if (! Schema::hasTable('payment_fee_items')) {
            Schema::create('payment_fee_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('foundation_id')->index();
                $table->string('section', 30)->index();
                $table->string('label');
                $table->decimal('amount', 15, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['foundation_id', 'section', 'sort_order']);
            });

            return;
        }

        $columns = [
            'foundation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('foundation_id')->nullable()->index(),
            'section' => fn (Blueprint $table) => $table->string('section', 30)->nullable()->index(),
            'label' => fn (Blueprint $table) => $table->string('label')->nullable(),
            'amount' => fn (Blueprint $table) => $table->decimal('amount', 15, 2)->default(0),
            'sort_order' => fn (Blueprint $table) => $table->unsignedInteger('sort_order')->default(0),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('payment_fee_items', $column)) {
                Schema::table('payment_fee_items', $definition);
            }
        }
    }

    public function down(): void
    {
        // Keep production payment settings and data intact.
    }
};
