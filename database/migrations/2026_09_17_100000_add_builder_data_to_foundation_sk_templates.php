<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('foundation_sk_templates', 'builder_data')) {
            Schema::table('foundation_sk_templates', function (Blueprint $table): void {
                $table->json('builder_data')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('foundation_sk_templates', 'builder_data')) {
            Schema::table('foundation_sk_templates', function (Blueprint $table): void {
                $table->dropColumn('builder_data');
            });
        }
    }
};
