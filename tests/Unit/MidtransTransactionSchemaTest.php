<?php

namespace Tests\Unit;

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MidtransTransactionSchemaTest extends TestCase
{
    public function test_mysql_user_reference_matches_signed_and_unsigned_parent_ids(): void
    {
        $statements = [];
        $connection = new MySqlConnection(null, 'schema_test', '', ['version' => '8.0.32']);
        $connection->useDefaultSchemaGrammar();
        Schema::shouldReceive('getColumnType')->with('users', 'id', true)->twice()
            ->andReturn('bigint(20)', 'bigint(20) unsigned');
        Schema::shouldReceive('hasTable')->with('midtrans_transactions')->twice()->andReturn(false);
        Schema::shouldReceive('create')->twice()->with('midtrans_transactions', Mockery::on(
            function ($definition) use ($connection, &$statements): bool {
                $blueprint = new Blueprint($connection, 'midtrans_transactions');
                $blueprint->create();
                $definition($blueprint);
                $statements[] = implode("\n", $blueprint->toSql());

                return true;
            },
        ));
        // Skip executing the CHECK statement; only compile SQL, with no database access.
        DB::shouldReceive('getDriverName')->twice()->andReturn('sqlite');
        $migration = require base_path('database/migrations/2026_09_21_030000_create_midtrans_transactions_table.php');
        $migration->up();
        $migration->up();

        $this->assertStringContainsString('`user_id` bigint null', $statements[0]);
        $this->assertStringContainsString('`user_id` bigint unsigned null', $statements[1]);
        foreach ($statements as $sql) {
            $this->assertStringContainsString('foreign key (`user_id`) references `users` (`id`) on delete set null', $sql);
            $this->assertStringNotContainsString('alter table `users`', $sql);
        }
    }

    public function test_migration_defines_isolated_midtrans_table_and_required_constraints(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_09_21_030000_create_midtrans_transactions_table.php'));

        $this->assertStringContainsString("Schema::create('midtrans_transactions'", $migration);
        $this->assertStringContainsString("constrained('payment_invoices')", $migration);
        $this->assertStringContainsString("constrained('users')", $migration);
        $this->assertStringContainsString('->unique()', $migration);
        $this->assertStringContainsString('gross_amount > 0', $migration);
        $this->assertStringNotContainsString('legacy_payment_id', $migration);
        $this->assertStringNotContainsString('payment_transactions', $migration);
    }
}
