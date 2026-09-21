<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->normalize('student_enrollments', [
                'k1', 'k2', 'k3', 'k4', 'k5', 'k6', 'k7', 'k8', 'k9', 'total',
            ]);
            $this->normalize('educator_recaps', [
                'asn_certified',
                'asn_uncertified',
                'foundation_certified_inpassing',
                'foundation_uncertified',
                'total',
            ]);
        });
    }

    public function down(): void
    {
        // Normalisasi data tidak dibalik agar format tahun aplikasi tetap konsisten.
    }

    /** @param array<int, string> $valueColumns */
    private function normalize(string $table, array $valueColumns): void
    {
        $rows = DB::table($table)
            ->orderBy('id')
            ->get()
            ->filter(fn (object $row): bool => preg_match('/^20\d{2}\/\d{2}$/', $row->academic_year) === 1);

        foreach ($rows as $row) {
            $startYear = substr($row->academic_year, 0, 4);
            $academicYear = $startYear.'/'.substr($startYear, 0, 2).substr($row->academic_year, -2);
            $existing = DB::table($table)
                ->where('school_id', $row->school_id)
                ->where('academic_year', $academicYear)
                ->first();

            if (! $existing) {
                DB::table($table)->where('id', $row->id)->update([
                    'academic_year' => $academicYear,
                    'updated_at' => now(),
                ]);

                continue;
            }

            $updates = collect($valueColumns)
                ->mapWithKeys(fn (string $column): array => [$column => $row->{$column}])
                ->all();
            $updates['legacy_snapshot'] = json_encode([
                'source' => json_decode((string) $row->legacy_snapshot, true),
                'target_before_normalization' => (array) $existing,
            ], JSON_UNESCAPED_SLASHES);
            $updates['updated_at'] = now();

            DB::table($table)->where('id', $existing->id)->update($updates);
            DB::table($table)->where('id', $row->id)->delete();
        }
    }
};
