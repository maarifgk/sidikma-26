<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decree_requirements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('position')->default(0)->index();
            $table->string('number', 20)->nullable();
            $table->text('description');
            $table->string('template_label', 100)->nullable();
            $table->string('template_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('decree_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('submitted')->index();
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
        });

        $now = now();

        DB::table('decree_requirements')->insert(array_map(
            fn (array $requirement): array => array_merge([
                'template_label' => null,
                'template_path' => null,
            ], $requirement),
            [
                [
                    'position' => 1,
                    'number' => '1.',
                    'description' => 'Mengisi surat pernyataan siap berkhidmat di LP. Ma\'arif NU Gunungkidul, yang bermaterai 10.000.',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'position' => 2,
                    'number' => '2.',
                    'description' => 'Mendapatkan surat rekomendasi dari Ketua MWC di lingkungan Madrasah/Sekolah tempat bekerja. Surat ditujukan kepada Ketua LP. Ma\'arif NU Gunungkidul.',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'position' => 3,
                    'number' => '3.',
                    'description' => 'Mendapatkan surat pernyataan dari Kepala Madrasah/Sekolah bahwa lembaga membutuhkan dan siap menerima guru/pegawai baru.',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'position' => 4,
                    'number' => '4.',
                    'description' => 'Mengirimkan kelengkapan administrasi berupa:',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'position' => 5,
                    'number' => null,
                    'description' => 'Elektronik Warga Nahdlatul Ulama Gunungkidul (EWANUGK).',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'position' => 6,
                    'number' => null,
                    'description' => 'Foto resmi (file JPG/PNG).',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'position' => 7,
                    'number' => null,
                    'description' => 'Ijazah terakhir (file PDF).',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'position' => 8,
                    'number' => null,
                    'description' => 'Surat permohonan dari Madrasah/Sekolah diketahui MWC setempat (file PDF).',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'position' => 9,
                    'number' => null,
                    'description' => 'Surat pernyataan siap berkhidmat di Ma\'arif (file PDF).',
                    'template_label' => 'Download PDF',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('decree_proposals');
        Schema::dropIfExists('decree_requirements');
    }
};
