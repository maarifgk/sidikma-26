<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decree_proposals', function (Blueprint $table): void {
            $table->string('partner_admin_number', 50)->nullable()->after('employee_id');
            $table->string('photo_path')->nullable()->after('notes');
            $table->string('diploma_path')->nullable()->after('photo_path');
            $table->string('application_letter_path')->nullable()->after('diploma_path');
            $table->string('service_statement_path')->nullable()->after('application_letter_path');
        });
    }

    public function down(): void
    {
        Schema::table('decree_proposals', function (Blueprint $table): void {
            $table->dropColumn([
                'partner_admin_number',
                'photo_path',
                'diploma_path',
                'application_letter_path',
                'service_statement_path',
            ]);
        });
    }
};
