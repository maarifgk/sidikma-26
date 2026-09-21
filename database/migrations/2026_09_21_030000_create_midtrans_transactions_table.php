<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midtrans_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_invoice_id')->constrained('payment_invoices')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_id')->unique();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->decimal('gross_amount', 15, 2);
            $table->string('payment_type')->nullable();
            $table->string('transaction_status', 30)->index();
            $table->string('fraud_status', 30)->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->string('source', 30)->default('midtrans_sandbox');
            $table->json('raw_status_snapshot')->nullable();
            $table->timestamps();

            $table->index('payment_invoice_id');
            $table->index('transaction_id');
        });

        Schema::table('midtrans_transactions', function (Blueprint $table): void {
            $table->check('gross_amount > 0', 'midtrans_transactions_gross_amount_positive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_transactions');
    }
};
