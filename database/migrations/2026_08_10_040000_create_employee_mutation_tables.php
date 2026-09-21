<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutation_requirements', function (Blueprint $table): void {
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

        Schema::create('employee_mutations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('mutation_type', 30)->index();
            $table->foreignId('origin_school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('destination_school_id')->constrained('schools')->restrictOnDelete();
            $table->string('request_letter_path');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['origin_school_id', 'destination_school_id']);
        });

        $now = now();

        DB::table('mutation_requirements')->insert([
            [
                'position' => 1,
                'number' => '1.',
                'description' => 'Mendapatkan Surat Permohonan Mutasi dari Madrasah/Sekolah Asal.',
                'template_label' => null,
                'template_path' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'position' => 2,
                'number' => '2.',
                'description' => 'Mengirimkan Surat Mutasi diketahui oleh Kepala Madrasah/Sekolah Asal.',
                'template_label' => 'Download PDF',
                'template_path' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_mutations');
        Schema::dropIfExists('mutation_requirements');
    }
};
