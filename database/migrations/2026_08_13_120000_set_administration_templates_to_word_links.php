<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'decree_requirements',
            'mutation_requirements',
            'activity_requirements',
            'correspondence_types',
            'proposal_requirements',
            'sipinter_requirements',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)
                    ->whereNotNull('template_path')
                    ->where('template_path', '!=', '')
                    ->update(['template_label' => 'Download Word']);
            }
        }
    }

    public function down(): void
    {
        // Label lama tidak dikembalikan agar tautan Word yang sudah tersimpan tetap konsisten.
    }
};
