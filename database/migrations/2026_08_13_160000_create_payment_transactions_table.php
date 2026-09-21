<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_payment_id')->unique();
            $table->unsignedBigInteger('legacy_invoice_id')->nullable()->index();
            $table->string('gateway_order_id')->nullable()->index();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30)->nullable();
            $table->string('status', 30)->index();
            $table->string('receipt_url')->nullable();
            $table->string('installment_group')->nullable()->index();
            $table->unsignedSmallInteger('installment_term')->nullable();
            $table->unsignedSmallInteger('installment_sequence')->nullable();
            $table->timestamp('transacted_at')->nullable()->index();
            $table->json('legacy_snapshot')->nullable();
            $table->timestamps();

            $table->index(['payment_invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
