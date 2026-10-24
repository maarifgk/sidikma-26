<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mutation_requirements') || DB::table('mutation_requirements')->exists()) {
            return;
        }

        $now = now();
        DB::table('mutation_requirements')->insert([
            [
                'position' => 1,
                'number' => '1.',
                'description' => 'Mendapatkan Surat Permohonan Mutasi dari Madrasah/Sekolah Asal.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'position' => 2,
                'number' => '2.',
                'description' => 'Mengirimkan Surat Mutasi diketahui oleh Kepala Madrasah/Sekolah Asal.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void {}
};
