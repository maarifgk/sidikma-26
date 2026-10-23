<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50)->index();
            $table->morphs('owner');
            $table->string('disk', 50)->default('documents');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64)->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $this->userIdColumn($table, 'uploaded_by');
            $table->index('uploaded_by');
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'document_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }

    /** Match the user foreign key to the actual users.id signedness. */
    private function userIdColumn(Blueprint $table, string $column): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() === 'mysql') {
            $columnType = $connection->selectOne(
                'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['users', 'id'],
            )?->COLUMN_TYPE;

            if (is_string($columnType) && ! str_contains(strtolower($columnType), 'unsigned')) {
                $table->bigInteger($column)->nullable();

                return;
            }
        }

        $table->foreignId($column)->nullable();
    }
};
