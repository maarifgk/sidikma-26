<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table): void {
                $table->id();
                $table->string('document_type', 100)->index();
                $table->string('decree_kind', 150)->nullable()->index();
                $table->string('decree_number', 180)->nullable()->index();
                $table->date('decree_date')->nullable()->index();
                $table->text('notes')->nullable();
                $table->string('owner_type');
                $table->unsignedBigInteger('owner_id');
                $table->string('disk', 50)->default('documents');
                $table->string('path')->unique();
                $table->string('original_name');
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('size')->default(0);
                $table->string('checksum', 64)->nullable()->index();
                $table->string('status', 20)->default('active')->index();
                $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['owner_type', 'owner_id', 'document_type', 'status']);
            });

            return;
        }

        $columns = [
            'document_type' => fn (Blueprint $table) => $table->string('document_type', 100)->nullable()->index(),
            'decree_kind' => fn (Blueprint $table) => $table->string('decree_kind', 150)->nullable()->index(),
            'decree_number' => fn (Blueprint $table) => $table->string('decree_number', 180)->nullable()->index(),
            'decree_date' => fn (Blueprint $table) => $table->date('decree_date')->nullable()->index(),
            'notes' => fn (Blueprint $table) => $table->text('notes')->nullable(),
            'owner_type' => fn (Blueprint $table) => $table->string('owner_type')->nullable(),
            'owner_id' => fn (Blueprint $table) => $table->unsignedBigInteger('owner_id')->nullable()->index(),
            'disk' => fn (Blueprint $table) => $table->string('disk', 50)->default('documents'),
            'path' => fn (Blueprint $table) => $table->string('path')->nullable(),
            'original_name' => fn (Blueprint $table) => $table->string('original_name')->nullable(),
            'mime_type' => fn (Blueprint $table) => $table->string('mime_type', 100)->nullable(),
            'size' => fn (Blueprint $table) => $table->unsignedBigInteger('size')->default(0),
            'checksum' => fn (Blueprint $table) => $table->string('checksum', 64)->nullable()->index(),
            'status' => fn (Blueprint $table) => $table->string('status', 20)->default('active')->index(),
            'uploaded_by' => fn (Blueprint $table) => $table->unsignedBigInteger('uploaded_by')->nullable()->index(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
            'deleted_at' => fn (Blueprint $table) => $table->timestamp('deleted_at')->nullable()->index(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('documents', $column)) {
                Schema::table('documents', $definition);
            }
        }
    }

    public function down(): void
    {
        // Keep production document records intact.
    }
};
