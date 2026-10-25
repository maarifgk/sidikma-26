<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_invoices')) {
            Schema::create('payment_invoices', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_invoice_id')->nullable()->unique();
                $table->unsignedBigInteger('foundation_id')->nullable()->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->string('academic_year', 20)->nullable()->index();
                $table->string('invoice_number')->nullable()->unique();
                $table->string('description')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('status', 30)->default('unpaid')->index();
                $table->string('payment_method', 30)->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            return;
        }

        $columns = [
            'legacy_invoice_id' => fn (Blueprint $table) => $table->unsignedBigInteger('legacy_invoice_id')->nullable()->unique(),
            'foundation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('foundation_id')->nullable()->index(),
            'school_id' => fn (Blueprint $table) => $table->unsignedBigInteger('school_id')->nullable()->index(),
            'user_id' => fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable()->index(),
            'employee_id' => fn (Blueprint $table) => $table->unsignedBigInteger('employee_id')->nullable()->index(),
            'academic_year' => fn (Blueprint $table) => $table->string('academic_year', 20)->nullable()->index(),
            'invoice_number' => fn (Blueprint $table) => $table->string('invoice_number')->nullable()->unique(),
            'description' => fn (Blueprint $table) => $table->string('description')->nullable(),
            'amount' => fn (Blueprint $table) => $table->decimal('amount', 15, 2)->default(0),
            'status' => fn (Blueprint $table) => $table->string('status', 30)->default('unpaid')->index(),
            'payment_method' => fn (Blueprint $table) => $table->string('payment_method', 30)->nullable(),
            'due_date' => fn (Blueprint $table) => $table->date('due_date')->nullable(),
            'paid_at' => fn (Blueprint $table) => $table->timestamp('paid_at')->nullable(),
            'notes' => fn (Blueprint $table) => $table->text('notes')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('payment_invoices', $column)) {
                Schema::table('payment_invoices', $definition);
            }
        }
    }

    public function down(): void
    {
        // Repair migration intentionally leaves production data intact.
    }
};
