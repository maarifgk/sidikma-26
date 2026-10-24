<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('secretariat_agendas')) {
            Schema::create('secretariat_agendas', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('legacy_agenda_id')->nullable()->unique();
                $table->unsignedBigInteger('foundation_id')->index();
                $table->text('activity');
                $table->date('implementation_date')->nullable();
                $table->string('officer');
                $table->string('status')->index();
                $table->text('notes')->nullable();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
                $table->index(['foundation_id', 'implementation_date']);
            });
        }

        foreach ([
            'legacy_agenda_id' => fn (Blueprint $table) => $table->unsignedBigInteger('legacy_agenda_id')->nullable()->unique(),
            'legacy_snapshot' => fn (Blueprint $table) => $table->json('legacy_snapshot')->nullable(),
        ] as $column => $definition) {
            if (! Schema::hasColumn('secretariat_agendas', $column)) {
                Schema::table('secretariat_agendas', $definition);
            }
        }
    }

    public function down(): void {}
};
