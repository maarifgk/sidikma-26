<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy hosting uses signed user IDs; fresh Laravel installs use unsigned IDs.
        $userIdUnsigned = str_contains(Schema::getColumnType('users', 'id', fullDefinition: true), 'unsigned');

        if (Schema::hasTable('midtrans_transactions')) {
            $this->resumeEmptyTable($userIdUnsigned);

            return;
        }

        Schema::create('midtrans_transactions', function (Blueprint $table) use ($userIdUnsigned): void {
            $table->id();
            $table->foreignId('payment_invoice_id')->constrained('payment_invoices')->restrictOnDelete();
            $table->foreignId('user_id')->unsigned($userIdUnsigned)->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_id')->unique();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->decimal('gross_amount', 15, 2);
            $table->string('payment_type')->nullable();
            $table->string('transaction_status', 30)->index();
            $table->string('fraud_status', 30)->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->string('source', 30)->default('midtrans_sandbox');
            $table->json('raw_status_snapshot')->nullable();
            $table->timestamps();

            $table->index('payment_invoice_id');
            $table->index('transaction_id');
        });

        // SQLite cannot add a named table constraint with ALTER TABLE.
        if (in_array(DB::getDriverName(), ['pgsql', 'mysql'], true)) {
            DB::statement('ALTER TABLE midtrans_transactions ADD CONSTRAINT midtrans_transactions_gross_amount_positive CHECK (gross_amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_transactions');
    }

    private function resumeEmptyTable(bool $userIdUnsigned): void
    {
        // MySQL DDL may leave the table behind when a later foreign key fails.
        // Never drop it: checkout may already have added columns referencing it.
        if (DB::table('midtrans_transactions')->exists()) {
            throw new RuntimeException('Pemulihan dihentikan: tabel Midtrans sudah berisi transaksi. Perlu pemeriksaan sebelum mengubah struktur.');
        }
        if (! Schema::hasColumns('midtrans_transactions', [
            'id', 'payment_invoice_id', 'user_id', 'order_id', 'transaction_id',
            'idempotency_key', 'gross_amount', 'payment_type', 'transaction_status',
            'fraud_status', 'settlement_time', 'expiry_time', 'source',
            'raw_status_snapshot', 'created_at', 'updated_at',
        ])) {
            throw new RuntimeException('Struktur tabel Midtrans tidak sesuai; pemulihan otomatis dihentikan.');
        }

        $currentUnsigned = str_contains(Schema::getColumnType('midtrans_transactions', 'user_id', fullDefinition: true), 'unsigned');
        if ($currentUnsigned !== $userIdUnsigned) {
            Schema::table('midtrans_transactions', function (Blueprint $table) use ($userIdUnsigned): void {
                $table->foreignId('user_id')->unsigned($userIdUnsigned)->nullable()->change();
            });
        }

        foreach (['payment_invoice_id' => 'payment_invoices', 'user_id' => 'users'] as $column => $parent) {
            $foreign = collect(Schema::getForeignKeys('midtrans_transactions'))->firstWhere('columns', [$column]);
            if ($foreign) {
                if ($foreign['foreign_table'] !== $parent || $foreign['foreign_columns'] !== ['id']) {
                    throw new RuntimeException('Foreign key Midtrans tidak sesuai; pemulihan dihentikan.');
                }

                continue;
            }
            Schema::table('midtrans_transactions', function (Blueprint $table) use ($column, $parent): void {
                $foreign = $table->foreign($column)->references('id')->on($parent);
                $column === 'user_id' ? $foreign->nullOnDelete() : $foreign->restrictOnDelete();
            });
        }

        foreach (['order_id', 'transaction_id', 'idempotency_key'] as $column) {
            if (! Schema::hasIndex('midtrans_transactions', [$column], 'unique')) {
                Schema::table('midtrans_transactions', fn (Blueprint $table) => $table->unique($column));
            }
        }
        foreach (['transaction_status', 'payment_invoice_id'] as $column) {
            if (! Schema::hasIndex('midtrans_transactions', [$column])) {
                Schema::table('midtrans_transactions', fn (Blueprint $table) => $table->index($column));
            }
        }
        if (DB::getDriverName() === 'mysql') {
            $hasCheck = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
                ->where('TABLE_NAME', 'midtrans_transactions')
                ->where('CONSTRAINT_NAME', 'midtrans_transactions_gross_amount_positive')
                ->where('CONSTRAINT_TYPE', 'CHECK')->exists();
            if (! $hasCheck) {
                DB::statement('ALTER TABLE midtrans_transactions ADD CONSTRAINT midtrans_transactions_gross_amount_positive CHECK (gross_amount > 0)');
            }
        }
    }
};
