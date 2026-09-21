<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_requirements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('position')->default(0)->index();
            $table->string('number', 20)->nullable();
            $table->text('description');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('proposal_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('school_name');
            $table->string('proposal_type');
            $table->string('request_file_path');
            $table->decimal('requested_amount', 15, 2);
            $table->string('bank_name');
            $table->string('bank_account_number', 100);
            $table->text('description')->nullable();
            $table->string('process_status', 30)->default('submitted')->index();
            $table->string('approval_file_path')->nullable();
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('proposal_requirements')->insert([
            ['position' => 1, 'number' => '1.', 'description' => 'Dokumen Surat Permohonan Bantuan/Proposal dalam bentuk File PDF', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 2, 'number' => '2.', 'description' => 'Jenis Permohonan Bantuan/Proposal', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 3, 'number' => '3.', 'description' => 'Nominal yang diajukan', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['position' => 4, 'number' => '4.', 'description' => 'Nama Bank Dan Nomor Rekening', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            [
                'position' => 5,
                'number' => 'NB:',
                'description' => "Jika terdapat kendala atau error silahkan hubungi admin LP. Ma'arif NU PCNU Gunungkidul",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_requests');
        Schema::dropIfExists('proposal_requirements');
    }
};
