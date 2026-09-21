<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table): void {
            $table->string('payment_method')->nullable()->after('status');
            $table->text('notes')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table): void {
            $table->dropColumn(['payment_method', 'notes']);
        });
    }
};
