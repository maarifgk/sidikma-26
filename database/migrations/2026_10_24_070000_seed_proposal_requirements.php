<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proposal_requirements') || DB::table('proposal_requirements')->exists()) {
            return;
        }

        $now = now();
        DB::table('proposal_requirements')->insert([
            [
                'position' => 1,
                'number' => '1.',
                'description' => 'Dokumen Surat Permohonan Bantuan/Proposal dalam bentuk File PDF.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'position' => 2,
                'number' => '2.',
                'description' => 'Jenis Permohonan Bantuan/Proposal.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'position' => 3,
                'number' => '3.',
                'description' => 'Nominal yang diajukan.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'position' => 4,
                'number' => '4.',
                'description' => 'Nama Bank dan Nomor Rekening.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'position' => 5,
                'number' => 'NB:',
                'description' => "Jika terdapat kendala atau error silakan hubungi admin LP. Ma'arif NU PCNU Gunungkidul.",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void {}
};
