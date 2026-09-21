<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batik_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('audience')->index();
            $table->string('education_level')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->string('size_label')->default('meter');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['foundation_id', 'name']);
        });

        Schema::create('batik_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('batik_products')->restrictOnDelete();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->timestamp('ordered_at');
            $table->decimal('quantity', 10, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('status')->index();
            $table->string('recipient_name')->nullable();
            $table->timestamps();

            $table->index(['foundation_id', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batik_orders');
        Schema::dropIfExists('batik_products');
    }
};
