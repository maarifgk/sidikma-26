<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('foundation_sk_documents') && Schema::hasColumn('foundation_sk_documents', 'sk_template_id')) {
            Schema::table('foundation_sk_documents', function (Blueprint $table): void {
                $table->foreignId('sk_template_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('foundation_sk_documents') && Schema::hasColumn('foundation_sk_documents', 'sk_template_id')) {
            Schema::table('foundation_sk_documents', function (Blueprint $table): void {
                $table->foreignId('sk_template_id')->nullable(false)->change();
            });
        }
    }
};
