<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_invoice_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table): void {
            $table->dropColumn('legacy_invoice_id');
        });
    }
};
