<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('decree_templates')) {
            Schema::create('decree_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('paper_size', 20)->default('A4');
                $table->string('orientation', 20)->default('portrait');
                $table->string('disk')->default('documents');
                $table->string('path')->nullable();
                $table->string('original_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->index(['is_active', 'name']);
            });

            return;
        }

        $columns = [
            'name' => fn (Blueprint $table) => $table->string('name')->nullable(),
            'paper_size' => fn (Blueprint $table) => $table->string('paper_size', 20)->default('A4'),
            'orientation' => fn (Blueprint $table) => $table->string('orientation', 20)->default('portrait'),
            'disk' => fn (Blueprint $table) => $table->string('disk')->default('documents'),
            'path' => fn (Blueprint $table) => $table->string('path')->nullable(),
            'original_name' => fn (Blueprint $table) => $table->string('original_name')->nullable(),
            'mime_type' => fn (Blueprint $table) => $table->string('mime_type')->nullable(),
            'size' => fn (Blueprint $table) => $table->unsignedBigInteger('size')->nullable(),
            'is_active' => fn (Blueprint $table) => $table->boolean('is_active')->default(true)->index(),
            'created_by' => fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable()->index(),
            'updated_by' => fn (Blueprint $table) => $table->unsignedBigInteger('updated_by')->nullable()->index(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('decree_templates', $column)) {
                Schema::table('decree_templates', $definition);
            }
        }
    }

    public function down(): void
    {
        // Keep production decree-template data intact.
    }
};
