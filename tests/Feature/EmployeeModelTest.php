<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_table_has_the_required_basic_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('employees', [
            'id',
            'foundation_id',
            'school_id',
            'user_id',
            'employee_code',
            'name',
            'nik',
            'nip',
            'nuptk',
            'employee_type',
            'employment_status',
            'gender',
            'birth_place',
            'birth_date',
            'phone',
            'email',
            'address',
            'is_active',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));
    }

    public function test_employee_can_be_created_with_casted_attributes(): void
    {
        $employee = Employee::factory()->create([
            'employee_code' => 'PEG-0001',
            'name' => 'Ahmad Guru',
            'employee_type' => Employee::TYPE_GURU,
            'gender' => Employee::GENDER_MALE,
            'birth_date' => '1990-05-10',
            'is_active' => true,
        ]);

        $employee->refresh();

        $this->assertSame('PEG-0001', $employee->employee_code);
        $this->assertSame(Employee::TYPE_GURU, $employee->employee_type);
        $this->assertSame('1990-05-10', $employee->birth_date?->format('Y-m-d'));
        $this->assertTrue($employee->is_active);
    }

    public function test_employee_uses_soft_deletes(): void
    {
        $employee = Employee::factory()->create();

        $employee->delete();

        $this->assertNull(Employee::query()->find($employee->getKey()));
        $this->assertNotNull(Employee::withTrashed()->find($employee->getKey()));
        $this->assertSoftDeleted('employees', ['id' => $employee->getKey()]);
    }

    public function test_employee_can_be_related_to_foundation_school_and_optional_user(): void
    {
        $foundation = $this->createFoundation('SATU');
        $school = $this->createSchool($foundation, 'Sekolah Satu', '12345678');
        $user = User::factory()->create();

        $employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertTrue($employee->foundation->is($foundation));
        $this->assertTrue($employee->school->is($school));
        $this->assertTrue($employee->user->is($user));
        $this->assertTrue($foundation->employees->contains($employee));
        $this->assertTrue($school->employees->contains($employee));
        $this->assertTrue($user->employee->is($employee));
    }

    public function test_employee_can_exist_without_organization_or_user_account(): void
    {
        $employee = Employee::factory()->create();

        $this->assertNull($employee->foundation);
        $this->assertNull($employee->school);
        $this->assertNull($employee->user);
    }

    public function test_one_user_account_cannot_be_assigned_to_multiple_employees(): void
    {
        $user = User::factory()->create();

        Employee::factory()->create(['user_id' => $user->getKey()]);

        $this->expectException(QueryException::class);

        Employee::factory()->create(['user_id' => $user->getKey()]);
    }

    public function test_school_must_belong_to_the_selected_foundation(): void
    {
        $firstFoundation = $this->createFoundation('SATU');
        $secondFoundation = $this->createFoundation('DUA');
        $school = $this->createSchool($firstFoundation, 'Sekolah Satu', '87654321');

        try {
            Employee::factory()->create([
                'foundation_id' => $secondFoundation->getKey(),
                'school_id' => $school->getKey(),
            ]);

            $this->fail('Pegawai dengan sekolah dari yayasan lain seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Sekolah/madrasah harus berada di bawah yayasan yang dipilih.',
                $exception->errors()['school_id'][0],
            );
        }

        $this->assertDatabaseCount('employees', 0);
    }

    private function createFoundation(string $suffix): Foundation
    {
        return Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-EMP-{$suffix}",
            'is_active' => true,
        ]);
    }

    private function createSchool(Foundation $foundation, string $name, string $npsn): School
    {
        return School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => $name,
            'npsn' => $npsn,
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }
}
