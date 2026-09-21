<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sipinter_updates', function (Blueprint $table): void {
            $table->string('npsn', 8)->nullable()->after('school_id');
            $table->text('school_address')->nullable()->after('npsn');
            $table->string('land_ownership', 100)->nullable()->after('school_address');
            $table->string('land_status', 100)->nullable()->after('land_ownership');
            $table->string('management_authority', 100)->nullable()->after('land_status');
            $table->boolean('uses_notarial_deed')->nullable()->after('management_authority');
            $table->string('asset_file_path')->nullable()->change();
            $table->string('recommendation_file_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sipinter_updates', function (Blueprint $table): void {
            $table->dropColumn([
                'npsn',
                'school_address',
                'land_ownership',
                'land_status',
                'management_authority',
                'uses_notarial_deed',
            ]);
            $table->string('asset_file_path')->nullable(false)->change();
            $table->string('recommendation_file_path')->nullable(false)->change();
        });
    }
};
