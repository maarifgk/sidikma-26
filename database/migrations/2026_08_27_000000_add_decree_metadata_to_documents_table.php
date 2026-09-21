<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('decree_kind', 150)->nullable()->after('document_type')->index();
            $table->string('decree_number', 180)->nullable()->after('decree_kind')->index();
            $table->date('decree_date')->nullable()->after('decree_number')->index();
            $table->text('notes')->nullable()->after('decree_date');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn(['decree_kind', 'decree_number', 'decree_date', 'notes']);
        });
    }
};
