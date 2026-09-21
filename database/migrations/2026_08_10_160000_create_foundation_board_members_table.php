<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foundations', function (Blueprint $table): void {
            $table->string('board_heading')->nullable()->after('banner_path');
            $table->string('board_term', 100)->nullable()->after('board_heading');
        });

        Schema::create('foundation_board_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('foundation_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('position');
            $table->string('name')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_board_members');

        Schema::table('foundations', function (Blueprint $table): void {
            $table->dropColumn(['board_heading', 'board_term']);
        });
    }
};
