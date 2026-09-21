<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('academic_year', 20)->index();
            $table->string('invoice_number')->unique();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('status')->index();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['foundation_id', 'school_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_invoices');
    }
};
