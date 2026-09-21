<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table): void {
            $table->unique(
                ['user_id', 'school_id', 'academic_year', 'description'],
                'payment_invoices_unique_account_bill',
            );
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table): void {
            $table->dropUnique('payment_invoices_unique_account_bill');
        });
    }
};
