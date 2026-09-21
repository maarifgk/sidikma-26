<?php

use App\Models\EmployeePosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (EmployeePosition::defaultPositions() as $position) {
            DB::table('employee_positions')->updateOrInsert(
                ['code' => $position['code']],
                [
                    'name' => $position['name'],
                    'category' => $position['category'],
                    'is_active' => true,
                    'deleted_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('employee_positions')
            ->whereIn('code', collect(EmployeePosition::defaultPositions())->pluck('code'))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('employee_assignments')
                ->whereColumn('employee_assignments.employee_position_id', 'employee_positions.id'))
            ->delete();
    }
};
