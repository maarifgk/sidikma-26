<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decree_proposals', function (Blueprint $table): void {
            $table->string('teaching_certificate_path')->nullable()->after('service_statement_path');
            $table->string('educator_certificate_path')->nullable()->after('teaching_certificate_path');
            $table->string('task_assignment_certificate_path')->nullable()->after('educator_certificate_path');
        });
    }

    public function down(): void
    {
        Schema::table('decree_proposals', function (Blueprint $table): void {
            $table->dropColumn([
                'teaching_certificate_path',
                'educator_certificate_path',
                'task_assignment_certificate_path',
            ]);
        });
    }
};
