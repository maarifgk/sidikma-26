<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_modules')) {
            Schema::create('learning_modules', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_module_id')->nullable()->unique();
                $table->unsignedBigInteger('foundation_id')->index();
                $table->string('class_name');
                $table->string('module_type');
                $table->unsignedTinyInteger('semester');
                $table->string('subject');
                $table->string('chapter');
                $table->string('file_path');
                $table->string('original_name');
                $table->timestamp('uploaded_at')->nullable();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void {}
};
