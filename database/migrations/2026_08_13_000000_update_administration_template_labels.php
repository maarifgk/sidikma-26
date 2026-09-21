<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            DB::table($table)
                ->where('template_label', 'Download PDF')
                ->update(['template_label' => 'Buka File Word / PDF']);
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            DB::table($table)
                ->where('template_label', 'Buka File Word / PDF')
                ->update(['template_label' => 'Download PDF']);
        }
    }

    private function tables(): array
    {
        return [
            'mutation_requirements',
            'sipinter_requirements',
            'proposal_requirements',
            'activity_requirements',
            'decree_requirements',
            'correspondence_types',
        ];
    }
};
