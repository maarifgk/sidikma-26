<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->string('class_name');
            $table->string('module_type');
            $table->unsignedTinyInteger('semester');
            $table->string('subject');
            $table->string('chapter');
            $table->string('file_path');
            $table->string('original_name');
            $table->timestamp('uploaded_at');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['foundation_id', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_modules');
    }
};
