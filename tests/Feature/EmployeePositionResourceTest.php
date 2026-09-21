<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeePositions\EmployeePositionResource;
use App\Filament\Resources\EmployeePositions\Pages\CreateEmployeePosition;
use App\Filament\Resources\EmployeePositions\Pages\EditEmployeePosition;
use App\Filament\Resources\EmployeePositions\Pages\ListEmployeePositions;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeePositionResourceTest extends TestCase
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

    public function test_position_management_is_not_shown_on_employee_page(): void
    {
        $position = EmployeePosition::factory()->create([
            'name' => 'AAA Posisi Pengujian',
        ]);

        Livewire::test(ListEmployees::class)
            ->assertActionDoesNotExist('positions');

        $this->get(EmployeePositionResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Data Posisi/Jabatan');
        $this->get(EmployeePositionResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk();
        $this->get(EmployeePositionResource::getUrl(
            'edit',
            ['record' => $position],
            panel: 'admin',
            isAbsolute: false,
        ))->assertOk();
    }

    public function test_admin_can_create_and_update_position(): void
    {
        Livewire::test(CreateEmployeePosition::class)
            ->set('data.code', ' jbt-kepala ')
            ->set('data.name', 'Kepala Sekolah')
            ->set('data.category', EmployeePosition::CATEGORY_STRUCTURAL)
            ->set('data.description', 'Pimpinan satuan pendidikan')
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $position = EmployeePosition::query()
            ->where('code', 'JBT-KEPALA')
            ->sole();

        $this->assertSame('JBT-KEPALA', $position->code);

        Livewire::test(EditEmployeePosition::class, ['record' => $position->getRouteKey()])
            ->set('data.name', 'Kepala Madrasah/Sekolah')
            ->set('data.is_active', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('employee_positions', [
            'id' => $position->getKey(),
            'code' => 'JBT-KEPALA',
            'name' => 'Kepala Madrasah/Sekolah',
            'category' => EmployeePosition::CATEGORY_STRUCTURAL,
            'is_active' => false,
        ]);
    }

    public function test_position_form_rejects_duplicate_and_invalid_code(): void
    {
        EmployeePosition::factory()->create(['code' => 'JBT-UNIK']);

        Livewire::test(CreateEmployeePosition::class)
            ->set('data.code', ' jbt-unik ')
            ->set('data.name', '')
            ->set('data.category', null)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasFormErrors([
                'code' => 'unique',
                'name' => 'required',
                'category' => 'required',
            ]);

        Livewire::test(CreateEmployeePosition::class)
            ->set('data.code', 'kode tidak valid')
            ->set('data.name', 'Posisi Baru')
            ->set('data.category', EmployeePosition::CATEGORY_SUPPORT)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasFormErrors(['code' => 'regex']);
    }

    public function test_position_can_be_soft_deleted_restored_and_force_deleted(): void
    {
        $position = EmployeePosition::factory()->create();

        Livewire::test(EditEmployeePosition::class, ['record' => $position->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertSoftDeleted('employee_positions', ['id' => $position->getKey()]);

        Livewire::test(EditEmployeePosition::class, ['record' => $position->getRouteKey()])
            ->callAction(RestoreAction::class)
            ->assertHasNoActionErrors();

        $position->delete();

        Livewire::test(EditEmployeePosition::class, ['record' => $position->getRouteKey()])
            ->callAction(ForceDeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('employee_positions', ['id' => $position->getKey()]);
    }

    public function test_position_with_assignment_cannot_be_force_deleted(): void
    {
        $assignment = EmployeeAssignment::factory()->create();
        $position = $assignment->position;
        $position->delete();

        $this->assertFalse(EmployeePositionResource::canForceDelete($position));

        Livewire::test(EditEmployeePosition::class, ['record' => $position->getRouteKey()])
            ->assertActionHidden(ForceDeleteAction::class);
    }

    public function test_position_list_filters_category_active_and_trashed_records(): void
    {
        $teaching = EmployeePosition::factory()->create([
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'is_active' => true,
        ]);
        $structural = EmployeePosition::factory()->create([
            'category' => EmployeePosition::CATEGORY_STRUCTURAL,
            'is_active' => false,
        ]);
        $deleted = EmployeePosition::factory()->create([
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'is_active' => true,
        ]);
        $deleted->delete();

        Livewire::test(ListEmployeePositions::class)
            ->set('tableRecordsPerPage', 50)
            ->filterTable('category', EmployeePosition::CATEGORY_TEACHING)
            ->assertCanSeeTableRecords([$teaching])
            ->assertCanNotSeeTableRecords([$structural, $deleted]);

        Livewire::test(ListEmployeePositions::class)
            ->set('tableRecordsPerPage', 50)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$structural])
            ->assertCanNotSeeTableRecords([$teaching, $deleted]);

        Livewire::test(ListEmployeePositions::class)
            ->set('tableRecordsPerPage', 50)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$deleted])
            ->assertCanNotSeeTableRecords([$teaching, $structural]);
    }

    public function test_operational_role_cannot_access_position_resource(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($schoolAdmin);

        $this->get(EmployeePositionResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertForbidden();

        $this->assertFalse(EmployeePositionResource::canViewAny());
    }
}
