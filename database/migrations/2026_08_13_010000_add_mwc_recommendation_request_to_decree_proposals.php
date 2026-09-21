<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decree_proposals', function (Blueprint $table): void {
            $table->string('mwc_recommendation_request_path')
                ->nullable()
                ->after('application_letter_path');
        });
    }

    public function down(): void
    {
        Schema::table('decree_proposals', function (Blueprint $table): void {
            $table->dropColumn('mwc_recommendation_request_path');
        });
    }
};
