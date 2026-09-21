<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decree_proposals', function (Blueprint $table): void {
            $table->foreignId('employee_id')->nullable()->change();
            $table->unsignedBigInteger('legacy_proposal_id')->nullable()->unique()->after('id');
            $table->string('candidate_name')->nullable()->after('employee_id');
            $table->string('candidate_email')->nullable()->after('candidate_name');
            $table->string('candidate_phone', 50)->nullable()->after('candidate_email');
            $table->string('candidate_school')->nullable()->after('candidate_phone');
            $table->json('legacy_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('decree_proposals', function (Blueprint $table): void {
            $table->dropColumn(['legacy_proposal_id', 'candidate_name', 'candidate_email', 'candidate_phone', 'candidate_school', 'legacy_snapshot']);
            $table->foreignId('employee_id')->nullable(false)->change();
        });
    }
};
