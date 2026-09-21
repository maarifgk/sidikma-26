<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_requirements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('position')->default(0)->index();
            $table->string('number', 20)->nullable();
            $table->text('description');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_activity_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('employee_name');
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('school_name')->nullable();
            $table->date('inactive_date')->index();
            $table->string('request_letter_path');
            $table->string('status', 30)->default('submitted')->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('activity_requirements')->insert([
            ['position' => 1, 'number' => '1.', 'description' => 'Nama Guru/Pegawai', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 2, 'number' => '2.', 'description' => 'Asal Madrasah/Sekolah', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 3, 'number' => '3.', 'description' => 'TMT Non Aktif', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 4, 'number' => '4.', 'description' => 'Surat Permohonan Non Aktif', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            [
                'position' => 5,
                'number' => 'KET:',
                'description' => 'Permohonan Aktivasi Guru/Pegawai kepada Yayasan harus menyertakan Surat Permohonan dari Madrasah/Sekolah Asal.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_activity_requests');
        Schema::dropIfExists('activity_requirements');
    }
};
