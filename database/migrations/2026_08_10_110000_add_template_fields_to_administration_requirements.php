<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_requirements', function (Blueprint $table): void {
            $table->string('template_label', 100)->nullable();
            $table->string('template_path')->nullable();
        });

        Schema::table('correspondence_types', function (Blueprint $table): void {
            $table->string('template_label', 100)->nullable();
            $table->string('template_path')->nullable();
        });

        Schema::table('proposal_requirements', function (Blueprint $table): void {
            $table->string('template_label', 100)->nullable();
            $table->string('template_path')->nullable();
        });

        Schema::table('sipinter_requirements', function (Blueprint $table): void {
            $table->string('template_label', 100)->nullable();
            $table->string('template_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('activity_requirements', function (Blueprint $table): void {
            $table->dropColumn(['template_label', 'template_path']);
        });

        Schema::table('correspondence_types', function (Blueprint $table): void {
            $table->dropColumn(['template_label', 'template_path']);
        });

        Schema::table('proposal_requirements', function (Blueprint $table): void {
            $table->dropColumn(['template_label', 'template_path']);
        });

        Schema::table('sipinter_requirements', function (Blueprint $table): void {
            $table->dropColumn(['template_label', 'template_path']);
        });
    }
};
