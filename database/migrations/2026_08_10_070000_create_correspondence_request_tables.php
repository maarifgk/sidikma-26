<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correspondence_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('position')->default(0)->index();
            $table->string('number', 20)->nullable();
            $table->text('name');
            $table->boolean('is_selectable')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('correspondence_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('school_name');
            $table->foreignId('correspondence_type_id')->nullable()->constrained('correspondence_types')->nullOnDelete();
            $table->string('type_name');
            $table->string('request_file_path');
            $table->string('response_file_path')->nullable();
            $table->string('process_status', 30)->default('submitted')->index();
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('correspondence_types')->insert([
            ['position' => 1, 'number' => '1.', 'name' => 'Surat Pernyataan', 'is_selectable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 2, 'number' => '2.', 'name' => 'Surat Rekomendasi', 'is_selectable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 3, 'number' => '3.', 'name' => 'Surat Perintah Tugas', 'is_selectable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 4, 'number' => '4.', 'name' => 'Surat Keterangan', 'is_selectable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            [
                'position' => 5,
                'number' => 'KET:',
                'name' => 'Permohonan Persuratan kepada Yayasan harus menyertakan Surat Permohonan dari Madrasah/Sekolah Asal',
                'is_selectable' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence_requests');
        Schema::dropIfExists('correspondence_types');
    }
};
