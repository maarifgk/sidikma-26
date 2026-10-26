<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('foundation_sk_templates')) {
            Schema::create('foundation_sk_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('paper_size')->default('A4');
                $table->string('orientation')->default('portrait');
                $table->text('html_template')->nullable();
                $table->text('css_template')->nullable();
                $table->json('placeholders')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('foundation_sk_number_settings')) {
            Schema::create('foundation_sk_number_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('periode', 20)->index();
                $table->string('nomor_pattern');
                $table->unsignedInteger('nomor_awal')->default(1);
                $table->unsignedInteger('nomor_berikutnya')->default(1);
                $table->unsignedTinyInteger('digit_nomor')->default(4);
                $table->string('nomor_text')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique('periode');
            });
        }

        if (! Schema::hasTable('foundation_sk_documents')) {
            Schema::create('foundation_sk_documents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('sk_template_id');
                $table->unsignedSmallInteger('tahun_sk')->index();
                $table->string('original_filename');
                $table->string('stored_filename');
                $table->string('file_path');
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('file_size');
                $table->string('source_type', 30)->default('single');
                $table->string('matched_by', 50)->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'tahun_sk', 'sk_template_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_sk_documents');
        Schema::dropIfExists('foundation_sk_number_settings');
    }
};
