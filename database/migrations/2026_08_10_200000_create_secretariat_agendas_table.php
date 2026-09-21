<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secretariat_agendas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->text('activity');
            $table->date('implementation_date');
            $table->string('officer');
            $table->string('status')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['foundation_id', 'implementation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secretariat_agendas');
    }
};
