<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sipinter_requirements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('position')->default(0)->index();
            $table->string('number', 20)->nullable();
            $table->text('description');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();

        DB::table('sipinter_requirements')->insert([
            ['position' => 1, 'number' => '1.', 'description' => 'Nama Madrasah/Sekolah, NPSN, dan alamat lengkap', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 2, 'number' => '2.', 'description' => 'Keterangan kepemilikan dan status tanah satuan pendidikan', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 3, 'number' => '3.', 'description' => 'Keterangan pengelolaan satuan pendidikan dan penggunaan akta notaris', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 4, 'number' => '4.', 'description' => 'Surat Permohonan Update Data Sipinter dalam bentuk file PDF', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            [
                'position' => 5,
                'number' => 'KET:',
                'description' => 'Lengkapi data dan dokumen dengan benar sebelum mengirim pembaruan Data Sipinter.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sipinter_requirements');
    }
};
