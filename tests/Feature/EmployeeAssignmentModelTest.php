<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeAssignmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_assignments_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('employee_assignments', [
            'id',
            'employee_id',
            'employee_position_id',
            'foundation_id',
            'school_id',
            'start_date',
            'end_date',
            'decree_number',
            'decree_date',
            'status',
            'is_primary',
            'notes',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));
    }

    public function test_assignment_relations_and_date_casts_work(): void
    {
        [$foundation, $school] = $this->createOrganization('RELASI', '12345678');
        $employee = Employee::factory()->create();
        $position = EmployeePosition::factory()->create();
        $assignment = EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'employee_position_id' => $position->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'decree_date' => '2026-06-25',
            'is_primary' => true,
        ]);

        $this->assertTrue($assignment->employee->is($employee));
        $this->assertTrue($assignment->position->is($position));
        $this->assertTrue($assignment->foundation->is($foundation));
        $this->assertTrue($assignment->school->is($school));
        $this->assertSame('2026-07-01', $assignment->start_date?->format('Y-m-d'));
        $this->assertSame('2027-06-30', $assignment->end_date?->format('Y-m-d'));
        $this->assertSame('2026-06-25', $assignment->decree_date?->format('Y-m-d'));
        $this->assertTrue($assignment->is_primary);
        $this->assertTrue($employee->assignments->contains($assignment));
        $this->assertTrue($position->assignments->contains($assignment));
        $this->assertTrue($foundation->employeeAssignments->contains($assignment));
        $this->assertTrue($school->employeeAssignments->contains($assignment));
    }

    public function test_school_must_belong_to_assignment_foundation(): void
    {
        [$firstFoundation, $school] = $this->createOrganization('PERTAMA', '11223344');
        [$secondFoundation] = $this->createOrganization('KEDUA', '55667788');

        try {
            EmployeeAssignment::factory()->create([
                'foundation_id' => $secondFoundation->getKey(),
                'school_id' => $school->getKey(),
            ]);

            $this->fail('Penugasan dengan sekolah dari yayasan lain seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Sekolah/madrasah harus berada di bawah yayasan yang dipilih.',
                $exception->errors()['school_id'][0],
            );
        }

        $this->assertDatabaseCount('employee_assignments', 0);
        $this->assertTrue($school->foundation->is($firstFoundation));
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        try {
            EmployeeAssignment::factory()->create([
                'start_date' => '2026-07-01',
                'end_date' => '2026-06-30',
            ]);

            $this->fail('Tanggal selesai sebelum tanggal mulai seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
                $exception->errors()['end_date'][0],
            );
        }

        $this->assertDatabaseCount('employee_assignments', 0);
    }

    public function test_employee_cannot_have_multiple_active_primary_assignments(): void
    {
        $employee = Employee::factory()->create();

        EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);

        try {
            EmployeeAssignment::factory()->create([
                'employee_id' => $employee->getKey(),
                'status' => EmployeeAssignment::STATUS_ACTIVE,
                'is_primary' => true,
            ]);

            $this->fail('Penugasan utama aktif kedua seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Pegawai hanya boleh memiliki satu penugasan utama yang aktif.',
                $exception->errors()['is_primary'][0],
            );
        }

        EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => false,
        ]);
        EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'status' => EmployeeAssignment::STATUS_COMPLETED,
            'is_primary' => true,
            'end_date' => now()->toDateString(),
        ]);

        $this->assertSame(3, $employee->assignments()->count());
    }

    public function test_assignment_uses_soft_deletes(): void
    {
        $assignment = EmployeeAssignment::factory()->create();

        $assignment->delete();

        $this->assertNull(EmployeeAssignment::query()->find($assignment->getKey()));
        $this->assertNotNull(EmployeeAssignment::withTrashed()->find($assignment->getKey()));
        $this->assertSoftDeleted('employee_assignments', ['id' => $assignment->getKey()]);
    }

    public function test_assignment_history_prevents_employee_from_being_force_deleted(): void
    {
        $assignment = EmployeeAssignment::factory()->create();

        $this->expectException(QueryException::class);

        $assignment->employee->forceDelete();
    }

    /**
     * @return array{Foundation, School}
     */
    private function createOrganization(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-ASSIGN-{$suffix}",
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Sekolah {$suffix}",
            'npsn' => $npsn,
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        return [$foundation, $school];
    }
}
