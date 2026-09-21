<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sipinter_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->restrictOnDelete();
            $table->string('request_file_path');
            $table->string('asset_file_path');
            $table->string('recommendation_file_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sipinter_updates');
    }
};
