<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mutation_requirements')) {
            Schema::create('mutation_requirements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('position')->default(0)->index();
            $table->string('number', 20)->nullable();
            $table->text('description');
            $table->string('template_label', 100)->nullable();
            $table->string('template_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            // User IDs are kept as nullable references without a database FK so
            // this module also works with legacy users tables on production.
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_mutations')) {
            Schema::create('employee_mutations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('mutation_type', 30)->index();
            $table->unsignedBigInteger('origin_school_id')->nullable()->index();
            $table->unsignedBigInteger('destination_school_id')->nullable()->index();
            $table->string('request_letter_path');
            $table->unsignedBigInteger('submitted_by')->nullable()->index();
            $table->timestamps();

            $table->index(['origin_school_id', 'destination_school_id']);
            });
        }

        $now = now();

        if (Schema::hasTable('mutation_requirements') && DB::table('mutation_requirements')->count() === 0) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_mutations');
        Schema::dropIfExists('mutation_requirements');
    }
};
