<?php

namespace Tests\Feature;

use App\Filament\Resources\SchoolProfiles\Pages\ListSchoolProfiles;
use App\Filament\Resources\SchoolProfiles\Pages\ViewSchoolProfile;
use App\Filament\Resources\SchoolProfiles\RelationManagers\SchoolProfileEmployeesRelationManager;
use App\Filament\Resources\SchoolProfiles\SchoolProfileResource;
use App\Models\EducatorRecap;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolProfileResourceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $foundation = Foundation::query()->create([
            'name' => 'LP Ma\'arif NU Gunungkidul',
            'code' => 'LP-MAARIF-GK',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Baleharjo',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'accreditation_status' => 'B',
            'accreditation_expiry_year' => 2029,
            'address' => 'Baleharjo, Wonosari',
            'land_status' => 'Wakaf',
            'land_area' => 1124,
            'has_land_certificate' => true,
            'has_bhpnu_ownership' => true,
            'phone' => '089612345678',
            'email' => 'baleharjo@example.test',
            'is_active' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'name' => 'Andar Styawan, M.Pd.',
            'employee_code' => '3403032003.550',
            'employee_type' => Employee::TYPE_GURU,
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $position = EmployeePosition::factory()->create([
            'name' => 'Mengajar Guru Kelas',
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'is_active' => true,
        ]);

        EmployeeAssignment::factory()->create([
            'employee_id' => $this->employee->getKey(),
            'employee_position_id' => $position->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'decree_number' => '001/SK/2026',
            'decree_period' => '2026/2027',
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);

        StudentEnrollment::query()->create([
            'school_id' => $this->school->getKey(),
            'academic_year' => '2026/2027',
            'k1' => 12,
            'k2' => 13,
            'submitted_by' => auth()->id(),
        ]);

        EducatorRecap::query()->create([
            'school_id' => $this->school->getKey(),
            'academic_year' => '2026/2027',
            'asn_certified' => 1,
            'asn_uncertified' => 0,
            'foundation_certified_inpassing' => 1,
            'foundation_uncertified' => 0,
            'submitted_by' => auth()->id(),
        ]);
    }

    public function test_profile_school_menu_opens_the_requested_table(): void
    {
        $this->get(SchoolProfileResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Profile Madrasah/Sekolah')
            ->assertSee('Nama Madrasah/Sekolah')
            ->assertSee('MI YAPPI Baleharjo')
            ->assertSee('View Profile');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Profile Sekolah');
    }

    public function test_profile_table_supports_search_and_view_action(): void
    {
        Livewire::test(ListSchoolProfiles::class)
            ->assertCanSeeTableRecords([$this->school])
            ->assertTableColumnExists('profile_action')
            ->searchTable('Baleharjo')
            ->assertCanSeeTableRecords([$this->school]);
    }

    public function test_empty_profile_table_does_not_show_create_school_menu(): void
    {
        $this->school->delete();

        $this->get(SchoolProfileResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Action')
            ->assertSee('Belum ada profil madrasah/sekolah')
            ->assertDontSee('Tambah Data Sekolah');
    }

    public function test_view_profile_page_displays_school_details(): void
    {
        $this->get(SchoolProfileResource::getUrl(
            'view',
            ['record' => $this->school],
            panel: 'admin',
            isAbsolute: false,
        ))
            ->assertOk()
            ->assertSee('MI YAPPI Baleharjo')
            ->assertSee('12345678')
            ->assertSee('Baleharjo, Wonosari')
            ->assertSee('baleharjo@example.test')
            ->assertSee('DATA MADRASAH/SEKOLAH')
            ->assertSee('Status Akreditasi')
            ->assertSee('Masa Akreditasi')
            ->assertSee('Status Tanah')
            ->assertSee('Kepemilikan Sertifikat Tanah')
            ->assertSee('Kepemilikan BHPNU')
            ->assertSee('Jumlah Siswa (2026/2027)')
            ->assertSee('25 Siswa')
            ->assertSee('Mengajar Guru Kelas')
            ->assertSee('1 Orang');
    }

    public function test_view_profile_uses_employee_data_from_master_data(): void
    {
        Livewire::test(SchoolProfileEmployeesRelationManager::class, [
            'ownerRecord' => $this->school,
            'pageClass' => ViewSchoolProfile::class,
        ])
            ->assertCanSeeTableRecords([$this->employee])
            ->assertSee('Andar Styawan, M.Pd.')
            ->assertSee('Guru Tetap Yayasan')
            ->assertSee('2026/2027')
            ->assertSee('Mengajar Guru Kelas')
            ->assertTableActionVisible('viewEmployee', $this->employee);
    }
}
