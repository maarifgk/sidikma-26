<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('batik_orders')
            ->whereNotNull('legacy_order_id')
            ->where('status', 'completed')
            ->update([
                'status' => 'collected',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Data status operasional tidak dikembalikan agar pesanan yang telah diproses tetap aman.
    }
};
