<?php

namespace Tests\Feature;

use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\RelationManagers\AssignmentsRelationManager;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeAssignmentRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($this->admin);
    }

    public function test_employee_edit_page_displays_assignment_history_manager(): void
    {
        $employee = Employee::factory()->create();
        $assignment = EmployeeAssignment::factory()->create(['employee_id' => $employee->getKey()]);

        $this->get(route('filament.admin.resources.employees.edit', ['record' => $employee]))
            ->assertOk();

        $this->assertSame(
            'Riwayat Penugasan',
            AssignmentsRelationManager::getTitle($employee, EditEmployee::class),
        );

        $this->relationManager($employee)
            ->assertCanSeeTableRecords([$assignment])
            ->assertSee($assignment->position->name);
    }

    public function test_admin_can_create_and_update_assignment_from_employee_page(): void
    {
        [$foundation, $school] = $this->createOrganization('CRUD', '12345678');
        $employee = Employee::factory()->create();
        $position = EmployeePosition::factory()->create([
            'name' => 'Kepala Sekolah',
            'category' => EmployeePosition::CATEGORY_STRUCTURAL,
        ]);

        $this->relationManager($employee)
            ->callTableAction(CreateAction::class, data: [
                'employee_position_id' => $position->getKey(),
                'foundation_id' => $foundation->getKey(),
                'school_id' => $school->getKey(),
                'status' => EmployeeAssignment::STATUS_ACTIVE,
                'start_date' => '2026-07-01',
                'end_date' => null,
                'decree_number' => 'SK-001/2026',
                'decree_date' => '2026-06-25',
                'is_primary' => true,
                'notes' => 'Penugasan kepala sekolah',
            ])
            ->assertHasNoFormErrors();

        $assignment = $employee->assignments()->sole();

        $this->assertSame($position->getKey(), $assignment->employee_position_id);
        $this->assertSame($school->getKey(), $assignment->school_id);
        $this->assertTrue($assignment->is_primary);

        $this->relationManager($employee)
            ->callTableAction(EditAction::class, $assignment, data: [
                'employee_position_id' => $position->getKey(),
                'foundation_id' => $foundation->getKey(),
                'school_id' => $school->getKey(),
                'status' => EmployeeAssignment::STATUS_COMPLETED,
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'decree_number' => 'SK-001/2026-REVISI',
                'decree_date' => '2026-06-25',
                'is_primary' => true,
                'notes' => 'Penugasan selesai',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('employee_assignments', [
            'id' => $assignment->getKey(),
            'status' => EmployeeAssignment::STATUS_COMPLETED,
            'end_date' => '2027-06-30 00:00:00',
            'decree_number' => 'SK-001/2026-REVISI',
        ]);
    }

    public function test_assignment_form_rejects_invalid_dates_school_and_second_primary(): void
    {
        [$firstFoundation, $firstSchool] = $this->createOrganization('PERTAMA', '11223344');
        [$secondFoundation] = $this->createOrganization('KEDUA', '55667788');
        $employee = Employee::factory()->create();
        $position = EmployeePosition::factory()->create();

        $this->relationManager($employee)
            ->callTableAction(CreateAction::class, data: [
                'employee_position_id' => $position->getKey(),
                'foundation_id' => $secondFoundation->getKey(),
                'school_id' => $firstSchool->getKey(),
                'status' => EmployeeAssignment::STATUS_ACTIVE,
                'start_date' => '2026-07-01',
                'end_date' => '2026-06-30',
                'is_primary' => false,
            ])
            ->assertHasFormErrors([
                'end_date' => 'after_or_equal',
            ]);

        EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'foundation_id' => $firstFoundation->getKey(),
            'school_id' => $firstSchool->getKey(),
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);

        $this->relationManager($employee)
            ->callTableAction(CreateAction::class, data: [
                'employee_position_id' => $position->getKey(),
                'foundation_id' => $firstFoundation->getKey(),
                'school_id' => $firstSchool->getKey(),
                'status' => EmployeeAssignment::STATUS_ACTIVE,
                'start_date' => '2026-07-01',
                'end_date' => null,
                'is_primary' => true,
            ])
            ->assertHasErrors(['is_primary']);

        $this->assertSame(1, $employee->assignments()->count());
    }

    public function test_assignment_can_be_soft_deleted_and_restored_but_not_force_deleted(): void
    {
        $employee = Employee::factory()->create();
        $assignment = EmployeeAssignment::factory()->create(['employee_id' => $employee->getKey()]);

        $this->relationManager($employee)
            ->callTableAction(DeleteAction::class, $assignment)
            ->assertHasNoFormErrors();

        $this->assertSoftDeleted('employee_assignments', ['id' => $assignment->getKey()]);

        $this->relationManager($employee)
            ->callTableAction(RestoreAction::class, $assignment)
            ->assertHasNoFormErrors();

        $this->assertNotNull(EmployeeAssignment::query()->find($assignment->getKey()));
        $this->assertFalse(Gate::allows('forceDelete', $assignment));

        $this->relationManager($employee)
            ->assertTableActionDoesNotExist(ForceDeleteAction::class, record: $assignment);
    }

    public function test_assignment_table_filters_records(): void
    {
        [$foundation, $school] = $this->createOrganization('FILTER', '66778899');
        $employee = Employee::factory()->create();
        $primaryPosition = EmployeePosition::factory()->create();
        $otherPosition = EmployeePosition::factory()->create();
        $primary = EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'employee_position_id' => $primaryPosition->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);
        $completed = EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'employee_position_id' => $otherPosition->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => null,
            'status' => EmployeeAssignment::STATUS_COMPLETED,
            'is_primary' => false,
            'end_date' => now()->toDateString(),
        ]);
        $deleted = EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'employee_position_id' => $otherPosition->getKey(),
            'foundation_id' => $foundation->getKey(),
            'status' => EmployeeAssignment::STATUS_INACTIVE,
            'is_primary' => false,
        ]);
        $deleted->delete();

        $this->relationManager($employee)
            ->filterTable('employee_position_id', $primaryPosition->getKey())
            ->assertCanSeeTableRecords([$primary])
            ->assertCanNotSeeTableRecords([$completed, $deleted]);

        $this->relationManager($employee)
            ->filterTable('school_id', $school->getKey())
            ->assertCanSeeTableRecords([$primary])
            ->assertCanNotSeeTableRecords([$completed, $deleted]);

        $this->relationManager($employee)
            ->filterTable('status', EmployeeAssignment::STATUS_COMPLETED)
            ->assertCanSeeTableRecords([$completed])
            ->assertCanNotSeeTableRecords([$primary, $deleted]);

        $this->relationManager($employee)
            ->filterTable('is_primary', true)
            ->assertCanSeeTableRecords([$primary])
            ->assertCanNotSeeTableRecords([$completed, $deleted]);

        $this->relationManager($employee)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$deleted])
            ->assertCanNotSeeTableRecords([$primary, $completed]);
    }

    public function test_operational_role_cannot_manage_assignments(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $assignment = EmployeeAssignment::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', EmployeeAssignment::class));
        $this->assertFalse(Gate::forUser($schoolAdmin)->allows('viewAny', EmployeeAssignment::class));
        $this->assertFalse(Gate::forUser($schoolAdmin)->allows('update', $assignment));
        $this->assertFalse(Gate::forUser($schoolAdmin)->allows('delete', $assignment));
    }

    private function relationManager(Employee $employee): mixed
    {
        return Livewire::test(AssignmentsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ]);
    }

    /**
     * @return array{Foundation, School}
     */
    private function createOrganization(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-RM-{$suffix}",
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
