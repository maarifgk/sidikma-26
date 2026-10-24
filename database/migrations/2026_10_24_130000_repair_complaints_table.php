<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('complaints')) {
            Schema::create('complaints', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('reference_number')->unique();
                $table->string('category', 50);
                $table->string('subject');
                $table->text('description');
                $table->json('attachments')->nullable();
                $table->string('status', 30)->default('submitted')->index();
                $table->text('admin_response')->nullable();
                $table->unsignedBigInteger('handled_by')->nullable()->index();
                $table->timestamp('handled_at')->nullable();
                $table->timestamps();
                $table->index(['submitted_by', 'created_at']);
            });
        }

        if (! Schema::hasColumn('complaints', 'attachments')) {
            Schema::table('complaints', function (Blueprint $table): void {
                $table->json('attachments')->nullable();
            });
        }
    }

    public function down(): void {}
};
