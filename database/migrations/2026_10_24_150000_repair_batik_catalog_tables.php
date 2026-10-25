<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('batik_products')) {
            Schema::create('batik_products', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('foundation_id')->index();
                $table->string('legacy_product_id')->nullable()->unique();
                $table->string('name');
                $table->string('audience')->index();
                $table->string('education_level')->nullable();
                $table->string('image_path')->nullable();
                $table->decimal('stock', 10, 2)->default(0);
                $table->decimal('price', 15, 2)->default(0);
                $table->string('size_label')->default('meter');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
                $table->unique(['foundation_id', 'name']);
            });
        }

        if (! Schema::hasTable('batik_orders')) {
            Schema::create('batik_orders', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_order_id')->nullable()->unique();
                $table->unsignedBigInteger('foundation_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('school_id')->index();
                $table->timestamp('ordered_at')->nullable();
                $table->decimal('quantity', 10, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('status')->index();
                $table->string('recipient_name')->nullable();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
                $table->index(['foundation_id', 'ordered_at']);
            });
        }
    }

    public function down(): void {}
};
