<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_heads')) {
            Schema::create('school_heads', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('employee_id')->index();
                $table->string('status', 20)->default('active')->index();
                $table->string('position', 100)->default('Kepala Madrasah/Sekolah');
                $table->date('started_at')->nullable()->index();
                $table->date('sk_start_date')->nullable()->index();
                $table->date('sk_end_date')->nullable()->index();
                $table->string('latest_sk_number', 180)->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['school_id', 'status', 'sk_end_date']);
            });
        }
    }

    public function down(): void {}
};
