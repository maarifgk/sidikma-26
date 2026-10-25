<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('correspondence_types') || DB::table('correspondence_types')->exists()) {
            return;
        }

        $now = now();
        DB::table('correspondence_types')->insert([
            ['position' => 1, 'number' => '1.', 'name' => 'Surat Pernyataan', 'is_selectable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 2, 'number' => '2.', 'name' => 'Surat Rekomendasi', 'is_selectable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 3, 'number' => '3.', 'name' => 'Surat Perintah Tugas', 'is_selectable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 4, 'number' => '4.', 'name' => 'Surat Keterangan', 'is_selectable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 5, 'number' => 'KET:', 'name' => 'Permohonan Persuratan kepada Yayasan harus menyertakan Surat Permohonan dari Madrasah/Sekolah Asal', 'is_selectable' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void {}
};
