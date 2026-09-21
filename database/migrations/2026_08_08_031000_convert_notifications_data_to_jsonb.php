<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE notifications
            ALTER COLUMN data TYPE jsonb
            USING CASE
                WHEN data IS NULL OR btrim(data) = '' THEN '{}'::jsonb
                ELSE data::jsonb
            END
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE notifications
            ALTER COLUMN data TYPE text
            USING data::text
        SQL);
    }
};
