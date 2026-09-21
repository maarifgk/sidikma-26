<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foundations', function (Blueprint $table): void {
            $table->string('instagram')->nullable()->after('email');
            $table->string('banner_path')->nullable()->after('instagram');
        });
    }

    public function down(): void
    {
        Schema::table('foundations', function (Blueprint $table): void {
            $table->dropColumn(['instagram', 'banner_path']);
        });
    }
};
