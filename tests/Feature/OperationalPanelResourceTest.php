<?php

namespace Tests\Feature;

use App\Filament\Resources\CorrespondenceRequests\CorrespondenceRequestResource;
use App\Filament\Resources\DecreeProposals\DecreeProposalResource;
use App\Filament\Resources\EducatorRecaps\EducatorRecapResource;
use App\Filament\Resources\EmployeeActivityRequests\EmployeeActivityRequestResource;
use App\Filament\Resources\EmployeeMutations\EmployeeMutationResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\ProposalRequests\ProposalRequestResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Filament\Resources\SipinterUpdates\SipinterUpdateResource;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Models\EducatorRecap;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalPanelResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_school_admin_can_open_operational_resources_and_navigation(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($schoolAdmin);

        $this->get('/app')
            ->assertOk()
            ->assertDontSee('Profile Lembaga')
            ->assertSee('Asal Madrasah')
            ->assertSee('Administrasi')
            ->assertSee('Kelembagaan')
            ->assertSee('Pesanan Batik');

        $this->get($this->schoolIndexUrl())->assertOk();

        foreach ($this->administrationUrls() as $url) {
            $this->get($url)->assertOk();
        }

        foreach ($this->institutionUrls() as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(DecreeProposalResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertSee('Usulan SK Baru')
            ->assertSee('Update Data Sipinter')
            ->assertSee('Mutasi')
            ->assertSee('Keaktifan')
            ->assertSee('Persuratan')
            ->assertSee('Pengajuan Proposal');

        $this->get(EmployeeResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertSee('Tenaga Pendidik')
            ->assertSee('Data Siswa')
            ->assertSee('Data Tenaga Pendidik');
    }

    public function test_teacher_can_open_read_only_operational_resource_lists(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->actingAs($teacher);

        $this->get($this->schoolIndexUrl())->assertOk();
        $this->get('/app')->assertDontSee('Administrasi');
        $this->get('/app')->assertDontSee('Kelembagaan');
        $this->get('/app')->assertDontSee('Pesanan Batik');

        $this->assertFalse(SchoolResource::canCreate());
        $this->assertFalse(DecreeProposalResource::canViewAny());
    }

    public function test_school_admin_and_parent_admin_use_the_same_scoped_institution_data(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'LP Ma\'arif NU Gunungkidul',
            'code' => 'LP-MAARIF-GK',
            'is_active' => true,
        ]);
        $assignedSchool = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI Sekolah Ditugaskan',
            'npsn' => '11112222',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $otherSchool = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI Sekolah Lain',
            'npsn' => '33334444',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $assignedSchool->getKey(),
            'status' => 'active',
        ]);

        $assignedEmployee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $assignedSchool->getKey(),
            'name' => 'Guru Sekolah Ditugaskan',
        ]);
        $otherEmployee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $otherSchool->getKey(),
            'name' => 'Guru Sekolah Lain',
        ]);
        StudentEnrollment::query()->create([
            'school_id' => $assignedSchool->getKey(),
            'academic_year' => StudentEnrollment::currentAcademicYear(),
            'k1' => 17,
            'submitted_by' => $schoolAdmin->getKey(),
        ]);
        StudentEnrollment::query()->create([
            'school_id' => $otherSchool->getKey(),
            'academic_year' => StudentEnrollment::currentAcademicYear(),
            'k1' => 29,
            'submitted_by' => $schoolAdmin->getKey(),
        ]);
        EducatorRecap::query()->create([
            'school_id' => $assignedSchool->getKey(),
            'academic_year' => EducatorRecap::currentAcademicYear(),
            'asn_certified' => 3,
            'submitted_by' => $schoolAdmin->getKey(),
        ]);
        EducatorRecap::query()->create([
            'school_id' => $otherSchool->getKey(),
            'academic_year' => EducatorRecap::currentAcademicYear(),
            'asn_certified' => 7,
            'submitted_by' => $schoolAdmin->getKey(),
        ]);

        $this->actingAs($schoolAdmin);
        $this->get(EmployeeResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertOk()
            ->assertSee($assignedEmployee->name)
            ->assertDontSee($otherEmployee->name);
        $this->get(StudentEnrollmentResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertOk()
            ->assertSee($assignedSchool->name)
            ->assertDontSee($otherSchool->name);
        $this->get(EducatorRecapResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertOk()
            ->assertSee($assignedSchool->name)
            ->assertDontSee($otherSchool->name);

        $parentAdmin = User::factory()->create();
        $parentAdmin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($parentAdmin);
        $this->get(EmployeeResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee($assignedEmployee->name)
            ->assertSee($otherEmployee->name);
        $this->get(StudentEnrollmentResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee($assignedSchool->name)
            ->assertSee($otherSchool->name);
        $this->get(EducatorRecapResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee($assignedSchool->name)
            ->assertSee($otherSchool->name);
    }

    private function schoolIndexUrl(): string
    {
        return SchoolResource::getUrl(panel: 'app', isAbsolute: false);
    }

    /** @return array<int, string> */
    private function administrationUrls(): array
    {
        return [
            DecreeProposalResource::getUrl(panel: 'app', isAbsolute: false),
            SipinterUpdateResource::getUrl(panel: 'app', isAbsolute: false),
            EmployeeMutationResource::getUrl(panel: 'app', isAbsolute: false),
            EmployeeActivityRequestResource::getUrl(panel: 'app', isAbsolute: false),
            CorrespondenceRequestResource::getUrl(panel: 'app', isAbsolute: false),
            ProposalRequestResource::getUrl(panel: 'app', isAbsolute: false),
        ];
    }

    /** @return array<int, string> */
    private function institutionUrls(): array
    {
        return [
            EmployeeResource::getUrl(panel: 'app', isAbsolute: false),
            StudentEnrollmentResource::getUrl(panel: 'app', isAbsolute: false),
            EducatorRecapResource::getUrl(panel: 'app', isAbsolute: false),
        ];
    }
}
