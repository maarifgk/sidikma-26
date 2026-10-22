<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payment_invoices', 'user_id')) {
            Schema::table('payment_invoices', function (Blueprint $table): void {
                // Keep compatibility with the existing production users table.
                $table->unsignedBigInteger('user_id')->nullable()->after('school_id')->index();
            });
        }

        DB::table('payment_invoices')
            ->whereNotNull('employee_id')
            ->orderBy('id')
            ->each(function (object $invoice): void {
                $userId = DB::table('employees')
                    ->where('id', $invoice->employee_id)
                    ->value('user_id');

                if ($userId !== null) {
                    DB::table('payment_invoices')
                        ->where('id', $invoice->id)
                        ->update(['user_id' => $userId]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('payment_invoices', 'user_id')) {
            Schema::table('payment_invoices', function (Blueprint $table): void {
                $table->dropColumn('user_id');
            });
        }
    }
};
