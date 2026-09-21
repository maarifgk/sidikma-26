<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->index('event');
                $table->index('created_at');
                $table->index(['log_name', 'event', 'created_at'], 'activity_log_module_event_time_index');
                $table->index(['subject_type', 'created_at'], 'activity_log_subject_type_time_index');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->dropIndex(['event']);
                $table->dropIndex(['created_at']);
                $table->dropIndex('activity_log_module_event_time_index');
                $table->dropIndex('activity_log_subject_type_time_index');
            });
    }
};
