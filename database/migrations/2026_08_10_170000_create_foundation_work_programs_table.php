<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundation_work_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('implementation_date')->nullable();
            $table->decimal('budget', 15, 2)->default(0);
            $table->string('status')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['foundation_id', 'implementation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_work_programs');
    }
};
