<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_records', function (Blueprint $table): void {
            $table->id();
            $table->string('source_table', 100)->index();
            $table->unsignedBigInteger('source_id');
            $table->jsonb('payload');
            $table->string('migration_note')->nullable();
            $table->timestamps();
            $table->unique(['source_table', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_records');
    }
};
