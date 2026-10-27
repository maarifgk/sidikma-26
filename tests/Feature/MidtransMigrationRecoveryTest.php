<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class MidtransMigrationRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('payment_invoices', fn (Blueprint $table) => $table->id());
        // Match the state after CREATE TABLE succeeds but FK/index commands fail.
        Schema::create('midtrans_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_invoice_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('order_id');
            $table->string('transaction_id')->nullable();
            $table->string('idempotency_key');
            $table->decimal('gross_amount', 15, 2);
            $table->string('payment_type')->nullable();
            $table->string('transaction_status');
            $table->string('fraud_status')->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->string('source');
            $table->json('raw_status_snapshot')->nullable();
            $table->timestamps();
            // The later checkout migration has already been applied on hosting.
            $table->text('snap_token')->nullable();
        });
        Schema::table('midtrans_transactions', function (Blueprint $table): void {
            $table->foreign('payment_invoice_id')->references('id')->on('payment_invoices')->restrictOnDelete();
        });
    }

    public function test_recovery_preserves_checkout_column_and_completes_constraints_without_duplicates(): void
    {
        $migration = require base_path('database/migrations/2026_09_21_030000_create_midtrans_transactions_table.php');
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('midtrans_transactions', 'snap_token'));
        foreach (['order_id', 'transaction_id', 'idempotency_key'] as $column) {
            $this->assertTrue(Schema::hasIndex('midtrans_transactions', [$column], 'unique'));
        }
        $foreign = collect(Schema::getForeignKeys('midtrans_transactions'));
        $this->assertCount(2, $foreign);
        $this->assertSame('users', $foreign->firstWhere('columns', ['user_id'])['foreign_table']);
        $this->assertSame('set null', $foreign->firstWhere('columns', ['user_id'])['on_delete']);
        $this->assertSame(0, DB::table('midtrans_transactions')->count());
    }

    public function test_recovery_refuses_populated_table_and_keeps_existing_transaction(): void
    {
        DB::table('payment_invoices')->insert(['id' => 1]);
        DB::table('midtrans_transactions')->insert([
            'payment_invoice_id' => 1, 'order_id' => 'EXISTING', 'idempotency_key' => 'EXISTING',
            'gross_amount' => 25000, 'transaction_status' => 'pending', 'source' => 'midtrans_sandbox',
        ]);
        $migration = require base_path('database/migrations/2026_09_21_030000_create_midtrans_transactions_table.php');
        try {
            $migration->up();
            $this->fail('Populated table must require review');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('sudah berisi transaksi', $exception->getMessage());
            $this->assertSame(1, DB::table('midtrans_transactions')->where('order_id', 'EXISTING')->count());
            $this->assertCount(1, Schema::getForeignKeys('midtrans_transactions'));
        }
    }
}
