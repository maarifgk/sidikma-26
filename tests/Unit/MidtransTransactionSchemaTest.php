<?php

namespace Tests\Unit;

use Tests\TestCase;

class MidtransTransactionSchemaTest extends TestCase
{
    public function test_migration_defines_isolated_midtrans_table_and_required_constraints(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_09_21_030000_create_midtrans_transactions_table.php'));

        $this->assertStringContainsString("Schema::create('midtrans_transactions'", $migration);
        $this->assertStringContainsString("constrained('payment_invoices')", $migration);
        $this->assertStringContainsString("constrained('users')", $migration);
        $this->assertStringContainsString("->unique()", $migration);
        $this->assertStringContainsString("gross_amount > 0", $migration);
        $this->assertStringNotContainsString('legacy_payment_id', $migration);
        $this->assertStringNotContainsString('payment_transactions', $migration);
    }
}
