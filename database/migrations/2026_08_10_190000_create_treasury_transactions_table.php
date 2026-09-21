<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('transaction_type')->index();
            $table->string('category');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->index(['foundation_id', 'transaction_date']);
            $table->index(['foundation_id', 'transaction_type', 'category'], 'treasury_foundation_type_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
