<?php

namespace Tests\Feature;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\Pages\ViewEmployee;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeResourceTest extends TestCase
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

    public function test_admin_can_open_employee_pages_and_navigation_links(): void
    {
        $employee = Employee::factory()->create(['name' => 'Guru Ahmad']);

        $this->get(EmployeeResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee($employee->name);
        $this->get(EmployeeResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk();
        $this->get(EmployeeResource::getUrl(
            'view',
            ['record' => $employee],
            panel: 'admin',
            isAbsolute: false,
        ))->assertOk();
        $this->get(EmployeeResource::getUrl(
            'edit',
            ['record' => $employee],
            panel: 'admin',
            isAbsolute: false,
        ))->assertOk();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Guru dan Pegawai');
    }

    public function test_admin_can_create_employee_with_organization_and_user_account(): void
    {
        $foundation = $this->createFoundation('SATU');
        $school = $this->createSchool($foundation, 'Madrasah Satu', '12345678');
        $position = EmployeePosition::factory()->create(['name' => 'Guru Kelas']);

        Livewire::test(CreateEmployee::class)
            ->set('data.employee_code', ' peg-001 ')
            ->set('data.name', 'Ahmad Guru')
            ->set('data.email', ' GURU@EXAMPLE.TEST ')
            ->set('data.nuptk', '6543210987654321')
            ->set('data.last_education', 'S1, 2025')
            ->set('data.program_study', 'Pendidikan Guru Madrasah Ibtidaiyah')
            ->set('data.school_id', $school->getKey())
            ->set('data.employment_status', 'GTY')
            ->set('data.birth_place', 'Gunungkidul')
            ->set('data.birth_date', '1990-05-10')
            ->set('data.assignment_start_date', '2026-07-01')
            ->set('data.position_id', $position->getKey())
            ->set('data.application_password', 'password-baru')
            ->set('data.decree_period', 'Juli')
            ->set('data.address', 'Jalan Pendidikan Nomor 1')
            ->call('create')
            ->assertHasNoFormErrors();

        $employee = Employee::query()->where('employee_code', 'PEG-001')->firstOrFail();
        $account = User::query()->where('email', 'guru@example.test')->firstOrFail();

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'PEG-001',
            'name' => 'Ahmad Guru',
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'user_id' => $account->getKey(),
            'email' => 'guru@example.test',
            'last_education' => 'S1, 2025',
            'program_study' => 'Pendidikan Guru Madrasah Ibtidaiyah',
            'is_active' => true,
        ]);
        $this->assertTrue($account->hasExactRoles([User::ROLE_GURU_PEGAWAI]));
        $this->assertTrue(Hash::check('password-baru', $account->password));
        $this->assertDatabaseHas('memberships', [
            'user_id' => $account->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('employee_assignments', [
            'employee_id' => $employee->getKey(),
            'employee_position_id' => $position->getKey(),
            'start_date' => '2026-07-01 00:00:00',
            'decree_period' => 'Juli',
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => 1,
        ]);
        $this->assertCount(0, $employee->documents);
    }

    public function test_admin_can_update_employee_without_losing_current_user_account(): void
    {
        $foundation = $this->createFoundation('EDIT');
        $school = $this->createSchool($foundation, 'Sekolah Edit', '87654321');
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'user_id' => $user->getKey(),
            'employee_code' => 'PEG-EDIT',
        ]);

        Livewire::test(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->assertSee('Password Akun Baru SIDIKMA')
            ->assertDontSee('Password Akun Lama SIDIKMA')
            ->assertFormFieldDoesNotExist('employee_type')
            ->set('data.name', 'Nama Pegawai Diperbarui')
            ->set('data.user_id', $user->getKey())
            ->set('data.is_active', false)
            ->set('data.new_account_password', 'PasswordBaru2026!')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->getKey(),
            'name' => 'Nama Pegawai Diperbarui',
            'employee_type' => $employee->employee_type,
            'user_id' => $user->getKey(),
            'is_active' => false,
        ]);
        $this->assertTrue(Hash::check('PasswordBaru2026!', $user->fresh()->password));
    }

    public function test_admin_can_reset_employee_account_password_from_table(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->getKey()]);

        Livewire::test(ListEmployees::class)
            ->assertDontSee('Password Akun Tenaga Pendidik')
            ->assertDontSee('Tersimpan (terenkripsi)')
            ->callTableAction('resetAccountPassword', $employee, data: [
                'password' => 'PasswordBaru2026!',
                'password_confirmation' => 'PasswordBaru2026!',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(Hash::check('PasswordBaru2026!', $user->fresh()->password));
    }

    public function test_admin_can_update_employee_ketugasan_from_edit_form(): void
    {
        $foundation = $this->createFoundation('KETUGASAN-EDIT');
        $school = $this->createSchool($foundation, 'Sekolah Ketugasan Edit', '10293847');
        $employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
        ]);
        $oldPosition = EmployeePosition::factory()->create();
        $newPosition = EmployeePosition::factory()->create();
        $assignment = EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'employee_position_id' => $oldPosition->getKey(),
            'foundation_id' => $employee->foundation_id,
            'school_id' => $employee->school_id,
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);

        Livewire::test(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->assertFormFieldExists('position_id')
            ->assertSet('data.position_id', $oldPosition->getKey())
            ->set('data.position_id', $newPosition->getKey())
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($newPosition->getKey(), $assignment->refresh()->employee_position_id);
    }

    public function test_form_rejects_duplicate_identifiers_and_invalid_contact_data(): void
    {
        $foundation = $this->createFoundation('VALIDASI');
        $school = $this->createSchool($foundation, 'Sekolah Validasi', '88776655');
        $position = EmployeePosition::factory()->create();
        Employee::factory()->create([
            'employee_code' => 'PEG-DUPLIKAT',
            'nuptk' => '5555666677778888',
            'email' => 'duplikat@example.test',
        ]);

        Livewire::test(CreateEmployee::class)
            ->set('data.employee_code', ' peg-duplikat ')
            ->set('data.name', 'Pegawai Duplikat')
            ->set('data.nuptk', ' 5555666677778888 ')
            ->set('data.email', ' DUPLIKAT@EXAMPLE.TEST ')
            ->set('data.school_id', $school->getKey())
            ->set('data.employment_status', 'GTY')
            ->set('data.assignment_start_date', '2026-07-01')
            ->set('data.position_id', $position->getKey())
            ->set('data.application_password', 'password-baru')
            ->call('create')
            ->assertHasFormErrors([
                'employee_code' => 'unique',
                'nuptk' => 'unique',
                'email' => 'unique',
            ]);

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_required_school_position_tmt_and_password_are_validated(): void
    {
        Livewire::test(CreateEmployee::class)
            ->set('data.employee_code', 'PEG-TANPA-PENUGASAN')
            ->set('data.name', 'Pegawai Tanpa Penugasan')
            ->set('data.email', 'tanpa-penugasan@example.test')
            ->set('data.employment_status', 'GTY')
            ->set('data.school_id', null)
            ->set('data.assignment_start_date', null)
            ->set('data.position_id', null)
            ->set('data.application_password', null)
            ->call('create')
            ->assertHasFormErrors([
                'school_id' => 'required',
                'assignment_start_date' => 'required',
                'position_id' => 'required',
                'application_password' => 'required',
            ]);

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_employee_can_be_soft_deleted_restored_and_force_deleted(): void
    {
        $employee = Employee::factory()->create();

        Livewire::test(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertSoftDeleted('employees', ['id' => $employee->getKey()]);

        Livewire::test(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->callAction(RestoreAction::class)
            ->assertHasNoActionErrors();

        $this->assertNotNull(Employee::query()->find($employee->getKey()));

        $employee->delete();

        Livewire::test(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->callAction(ForceDeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('employees', ['id' => $employee->getKey()]);
    }

    public function test_school_admin_uses_employee_resource_from_operational_panel_only(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($schoolAdmin);

        $this->get(EmployeeResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertForbidden();

        filament()->setCurrentPanel(filament()->getPanel('app'));

        $this->get(EmployeeResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertOk();
        $this->assertTrue(EmployeeResource::canViewAny());
    }

    public function test_employee_list_can_be_searched(): void
    {
        $found = Employee::factory()->create([
            'employee_code' => 'PEG-CARI-001',
            'name' => 'Guru Yang Dicari',
        ]);
        $hidden = Employee::factory()->create([
            'employee_code' => 'PEG-LAIN-002',
            'name' => 'Pegawai Lain',
        ]);

        Livewire::test(ListEmployees::class)
            ->searchTable('PEG-CARI-001')
            ->assertCanSeeTableRecords([$found])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_employee_list_matches_required_layout_and_displays_current_assignment(): void
    {
        $foundation = $this->createFoundation('TAMPILAN');
        $school = $this->createSchool($foundation, 'MI YAPPI Baleharjo', '99887766');
        $position = EmployeePosition::factory()->create(['name' => 'Kepala Madrasah/Sekolah']);
        $employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'employee_code' => '3403032003.550',
            'name' => 'Andar Styawan, M.Pd.',
            'employment_status' => 'GTY',
        ]);
        EmployeeAssignment::factory()->create([
            'employee_id' => $employee->getKey(),
            'employee_position_id' => $position->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'decree_number' => '001/SK/VII/2026',
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);

        Livewire::test(ListEmployees::class)
            ->assertSee('Guru/Pegawai')
            ->assertSee('Image')
            ->assertSee('Nama Lengkap')
            ->assertSee('EWANUGK')
            ->assertSee('Asal Madrasah')
            ->assertSee('Status Kepegawaian')
            ->assertSee('Ketugasan')
            ->assertSee('Andar Styawan, M.Pd.')
            ->assertSee('3403032003.550')
            ->assertSee('MI YAPPI Baleharjo')
            ->assertSee('Guru Tetap Yayasan')
            ->assertSee('Kepala Madrasah/Sekolah')
            ->assertDontSee('001/SK/VII/2026')
            ->assertTableActionVisible(EditAction::class, $employee)
            ->assertTableActionVisible(ViewAction::class, $employee)
            ->assertTableActionVisible(DeleteAction::class, $employee);
    }

    public function test_employee_form_has_photo_field_and_view_page_is_available(): void
    {
        $employee = Employee::factory()->create();

        Livewire::test(CreateEmployee::class)
            ->assertFormFieldExists('avatar_path');

        Livewire::test(ViewEmployee::class, ['record' => $employee->getRouteKey()])
            ->assertStatus(200)
            ->assertSee($employee->name);
    }

    public function test_add_employee_form_matches_reference_layout(): void
    {
        Livewire::test(CreateEmployee::class)
            ->assertSee('Tambah Guru/Pegawai')
            ->assertSee('EWANUGK')
            ->assertSee('NAMA LENGKAP')
            ->assertSee('EMAIL')
            ->assertSee('NUPTK/NPK')
            ->assertSee('PENDIDIKAN TERAKHIR DAN TAHUN LULUS')
            ->assertSee('PROGRAM STUDI')
            ->assertSee('ASAL MADRASAH/SEKOLAH')
            ->assertSee('STATUS KEPEGAWAIAN')
            ->assertSee('TEMPAT LAHIR')
            ->assertSee('TANGGAL LAHIR')
            ->assertSee('TMT (TERHITUNG MULAI TANGGAL)')
            ->assertSee('KETUGASAN')
            ->assertSee('PASSWORD BARU APLIKASI')
            ->assertSee('FOTO')
            ->assertSee('PERIODE SK YAYASAN')
            ->assertSee('Januari')
            ->assertSee('Juli')
            ->assertDontSee('UPLOAD SK YAYASAN')
            ->assertSee('ALAMAT')
            ->assertSee('Simpan')
            ->assertSee('Kembali');
    }

    public function test_employee_can_be_deleted_from_list_action(): void
    {
        $employee = Employee::factory()->create();

        Livewire::test(ListEmployees::class)
            ->callTableAction(DeleteAction::class, $employee);

        $this->assertSoftDeleted('employees', ['id' => $employee->getKey()]);
    }

    public function test_employee_list_can_be_filtered_individually_and_in_combination(): void
    {
        $firstFoundation = $this->createFoundation('FILTER-SATU');
        $secondFoundation = $this->createFoundation('FILTER-DUA');
        $firstSchool = $this->createSchool($firstFoundation, 'Sekolah Filter Satu', '22334455');
        $secondSchool = $this->createSchool($secondFoundation, 'Sekolah Filter Dua', '66778899');

        $teacher = Employee::factory()->create([
            'foundation_id' => $firstFoundation->getKey(),
            'school_id' => $firstSchool->getKey(),
            'employee_type' => Employee::TYPE_GURU,
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);
        $staff = Employee::factory()->create([
            'foundation_id' => $firstFoundation->getKey(),
            'school_id' => $firstSchool->getKey(),
            'employee_type' => Employee::TYPE_PEGAWAI,
            'employment_status' => 'PNS',
            'is_active' => false,
        ]);
        $otherTeacher = Employee::factory()->create([
            'foundation_id' => $secondFoundation->getKey(),
            'school_id' => $secondSchool->getKey(),
            'employee_type' => Employee::TYPE_GURU,
            'employment_status' => 'GTT',
            'is_active' => true,
        ]);
        $deleted = Employee::factory()->create([
            'foundation_id' => $firstFoundation->getKey(),
            'school_id' => $firstSchool->getKey(),
            'employee_type' => Employee::TYPE_PEGAWAI,
            'employment_status' => 'PPPK',
            'is_active' => true,
        ]);
        $deleted->delete();

        Livewire::test(ListEmployees::class)
            ->filterTable('school_id', $secondSchool->getKey())
            ->assertCanSeeTableRecords([$otherTeacher])
            ->assertCanNotSeeTableRecords([$teacher, $staff, $deleted]);

        Livewire::test(ListEmployees::class)
            ->filterTable('employee_type', Employee::TYPE_GURU)
            ->assertCanSeeTableRecords([$teacher, $otherTeacher])
            ->assertCanNotSeeTableRecords([$staff, $deleted]);

        Livewire::test(ListEmployees::class)
            ->filterTable('employment_status', 'GTY')
            ->assertCanSeeTableRecords([$teacher])
            ->assertCanNotSeeTableRecords([$staff, $otherTeacher, $deleted]);

        Livewire::test(ListEmployees::class)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$staff])
            ->assertCanNotSeeTableRecords([$teacher, $otherTeacher, $deleted]);

        Livewire::test(ListEmployees::class)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$deleted])
            ->assertCanNotSeeTableRecords([$teacher, $staff, $otherTeacher]);

        Livewire::test(ListEmployees::class)
            ->filterTable('school_id', $firstSchool->getKey())
            ->filterTable('employee_type', Employee::TYPE_PEGAWAI)
            ->filterTable('employment_status', 'PNS')
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$staff])
            ->assertCanNotSeeTableRecords([$teacher, $otherTeacher, $deleted]);
    }

    private function createFoundation(string $suffix): Foundation
    {
        return Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-RESOURCE-{$suffix}",
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
