<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('foundation_sk_templates')) {
            return;
        }

        $columns = [
            'slug' => fn (Blueprint $table) => $table->string('slug')->nullable()->unique(),
            'document_title' => fn (Blueprint $table) => $table->string('document_title')->nullable(),
            'description' => fn (Blueprint $table) => $table->text('description')->nullable(),
            'content' => fn (Blueprint $table) => $table->text('content')->nullable(),
            'custom_css' => fn (Blueprint $table) => $table->text('custom_css')->nullable(),
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('foundation_sk_templates', $name)) {
                Schema::table('foundation_sk_templates', $definition);
            }
        }
    }

    public function down(): void
    {
        // Kept intentionally non-destructive: these columns may contain template data.
    }
};
