<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_settings', function (Blueprint $table): void {
            $table->boolean('midtrans_is_production')->default(false);
        });
        Schema::table('midtrans_transactions', function (Blueprint $table): void {
            $table->text('snap_token')->nullable();
        });
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_payment_id')->nullable()->change();
            $table->foreignId('midtrans_transaction_id')->nullable()->unique()
                ->constrained('midtrans_transactions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Online payment history cannot safely be converted into legacy IDs.
        if (DB::table('payment_transactions')->whereNull('legacy_payment_id')->exists()) {
            throw new RuntimeException('Rollback diblokir: histori pembayaran online harus dipertahankan.');
        }
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('midtrans_transaction_id');
            $table->unsignedBigInteger('legacy_payment_id')->nullable(false)->change();
        });
        Schema::table('midtrans_transactions', fn (Blueprint $table) => $table->dropColumn('snap_token'));
        Schema::table('application_settings', fn (Blueprint $table) => $table->dropColumn('midtrans_is_production'));
    }
};
