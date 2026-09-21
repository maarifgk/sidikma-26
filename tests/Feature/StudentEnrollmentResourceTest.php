<?php

namespace Tests\Feature;

use App\Filament\Resources\StudentEnrollments\Pages\CreateStudentEnrollment;
use App\Filament\Resources\StudentEnrollments\Pages\EditStudentEnrollment;
use App\Filament\Resources\StudentEnrollments\Pages\ListStudentEnrollments;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Models\Foundation;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentEnrollmentResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($this->admin);

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
            'is_active' => true,
        ]);
        School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Mulusan',
            'npsn' => '87654321',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_student_data_page_and_create_form(): void
    {
        $currentYear = StudentEnrollment::currentAcademicYear();

        $this->get(StudentEnrollmentResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Data Jumlah Siswa per Tahun Pelajaran')
            ->assertSee('Total Siswa')
            ->assertSee('Madrasah Sudah Mengisi')
            ->assertSee('Madrasah Belum Mengisi')
            ->assertSee('Total Madrasah')
            ->assertSee($currentYear)
            ->assertSee('Tambah Data')
            ->assertSee('K1')
            ->assertSee('K9');

        $this->get(StudentEnrollmentResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tambah Data Jumlah Siswa')
            ->assertSee('TAHUN PELAJARAN')
            ->assertSee('SEKOLAH/MADRASAH');
    }

    public function test_admin_can_create_and_edit_student_enrollment_with_automatic_total(): void
    {
        $year = StudentEnrollment::currentAcademicYear();

        Livewire::test(CreateStudentEnrollment::class)
            ->fillForm([
                'academic_year' => $year,
                'school_id' => $this->school->getKey(),
                'k1' => 10,
                'k2' => 11,
                'k3' => 12,
                'k4' => 13,
                'k5' => 14,
                'k6' => 15,
                'k7' => 0,
                'k8' => 0,
                'k9' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $enrollment = StudentEnrollment::query()->sole();

        $this->assertSame(75, $enrollment->total);
        $this->assertTrue($enrollment->submittedBy->is($this->admin));

        Livewire::test(EditStudentEnrollment::class, ['record' => $enrollment->getRouteKey()])
            ->fillForm(['k1' => 20])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(85, $enrollment->fresh()->total);
    }

    public function test_year_selector_filters_table_and_summary(): void
    {
        $currentYear = StudentEnrollment::currentAcademicYear();
        $previousStart = ((int) explode('/', $currentYear)[0]) - 1;
        $previousYear = $previousStart.'/'.($previousStart + 1);

        $current = StudentEnrollment::query()->create([
            'school_id' => $this->school->getKey(),
            'academic_year' => $currentYear,
            'k1' => 25,
            'submitted_by' => $this->admin->getKey(),
        ]);
        $previous = StudentEnrollment::query()->create([
            'school_id' => $this->school->getKey(),
            'academic_year' => $previousYear,
            'k1' => 10,
            'submitted_by' => $this->admin->getKey(),
        ]);

        Livewire::test(ListStudentEnrollments::class)
            ->set('academicYear', $currentYear)
            ->assertCanSeeTableRecords([$current])
            ->assertCanNotSeeTableRecords([$previous])
            ->assertSee('25')
            ->assertSee('Madrasah Sudah Mengisi');
    }
}
