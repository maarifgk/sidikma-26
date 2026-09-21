<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_fee_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->string('section')->index();
            $table->string('label');
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['foundation_id', 'section', 'sort_order']);
        });

        Schema::table('foundations', function (Blueprint $table): void {
            $table->boolean('payment_fees_initialized')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('foundations', function (Blueprint $table): void {
            $table->dropColumn('payment_fees_initialized');
        });

        Schema::dropIfExists('payment_fee_items');
    }
};
