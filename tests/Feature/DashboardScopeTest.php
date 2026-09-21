<?php

namespace Tests\Feature;

use App\Filament\Widgets\ApprovalStatsWidget;
use App\Filament\Widgets\OrganizationStatsWidget;
use App\Filament\Widgets\RecentActivitiesWidget;
use App\Filament\Widgets\RecentApprovalsWidget;
use App\Models\Activity;
use App\Models\ApprovalRequest;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeActivityRequest;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\DashboardMetrics;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $schoolAdmin;

    private User $teacher;

    private Foundation $firstFoundation;

    private School $firstSchool;

    private Foundation $secondFoundation;

    private School $secondSchool;

    private Employee $teacherEmployee;

    private Employee $firstSchoolEmployee;

    private Employee $secondSchoolEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-07 12:00:00');
        $this->seed(RolePermissionSeeder::class);

        [$this->firstFoundation, $this->firstSchool] = $this->createOrganization(
            'PERTAMA',
            '11111111',
            'MI',
        );
        [$this->secondFoundation, $this->secondSchool] = $this->createOrganization(
            'KEDUA',
            '22222222',
            'MTs',
        );

        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);

        $this->schoolAdmin = User::factory()->create();
        $this->schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->assignMembership($this->schoolAdmin, $this->firstFoundation, $this->firstSchool);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->assignMembership($this->teacher, $this->firstFoundation, $this->firstSchool);

        $this->teacherEmployee = Employee::factory()->create([
            'foundation_id' => $this->firstFoundation->getKey(),
            'school_id' => $this->firstSchool->getKey(),
            'user_id' => $this->teacher->getKey(),
            'name' => 'Guru dengan Akun',
            'employee_type' => Employee::TYPE_GURU,
        ]);
        $this->firstSchoolEmployee = Employee::factory()->create([
            'foundation_id' => $this->firstFoundation->getKey(),
            'school_id' => $this->firstSchool->getKey(),
            'name' => 'Pegawai Sekolah Pertama',
            'employee_type' => Employee::TYPE_PEGAWAI,
        ]);
        $this->secondSchoolEmployee = Employee::factory()->create([
            'foundation_id' => $this->secondFoundation->getKey(),
            'school_id' => $this->secondSchool->getKey(),
            'name' => 'Guru Sekolah Kedua',
            'employee_type' => Employee::TYPE_GURU,
        ]);
    }

    public function test_admin_induk_dashboard_queries_all_data(): void
    {
        $metrics = app(DashboardMetrics::class);

        $this->assertSame(2, $metrics->foundations($this->admin)->count());
        $this->assertSame(2, $metrics->schools($this->admin)->count());
        $this->assertSame(3, $metrics->employees($this->admin)->count());
        $this->assertSame(3, $metrics->users($this->admin)->count());
        $this->assertSame('Seluruh sekolah/madrasah', $metrics->scopeLabel($this->admin));
    }

    public function test_school_admin_dashboard_only_queries_assigned_school(): void
    {
        $metrics = app(DashboardMetrics::class);

        $this->assertTrue($metrics->foundations($this->schoolAdmin)->sole()->is($this->firstFoundation));
        $this->assertTrue($metrics->schools($this->schoolAdmin)->sole()->is($this->firstSchool));
        $this->assertEqualsCanonicalizing(
            [$this->teacherEmployee->getKey(), $this->firstSchoolEmployee->getKey()],
            $metrics->employees($this->schoolAdmin)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$this->schoolAdmin->getKey(), $this->teacher->getKey()],
            $metrics->users($this->schoolAdmin)->pluck('id')->all(),
        );
        $this->assertSame($this->firstSchool->name, $metrics->scopeLabel($this->schoolAdmin));
    }

    public function test_teacher_dashboard_only_queries_own_employee_profile_and_account(): void
    {
        $metrics = app(DashboardMetrics::class);

        $this->assertTrue($metrics->employees($this->teacher)->sole()->is($this->teacherEmployee));
        $this->assertTrue($metrics->users($this->teacher)->sole()->is($this->teacher));
        $this->assertTrue($metrics->schools($this->teacher)->sole()->is($this->firstSchool));
        $this->assertFalse(
            $metrics->employees($this->teacher)->whereKey($this->firstSchoolEmployee->getKey())->exists(),
        );
    }

    public function test_document_and_approval_queries_inherit_organization_scope(): void
    {
        $teacherDocument = $this->createDocument($this->teacherEmployee, 'dokumen-guru.pdf');
        $firstSchoolDocument = $this->createDocument($this->firstSchoolEmployee, 'dokumen-sekolah-satu.pdf');
        $secondSchoolDocument = $this->createDocument($this->secondSchoolEmployee, 'dokumen-sekolah-dua.pdf');
        $teacherApproval = $teacherDocument->approvalRequests()->create();
        $firstSchoolApproval = $firstSchoolDocument->approvalRequests()->create();
        $secondSchoolApproval = $secondSchoolDocument->approvalRequests()->create();
        $metrics = app(DashboardMetrics::class);

        $this->assertEqualsCanonicalizing(
            [$teacherDocument->getKey(), $firstSchoolDocument->getKey(), $secondSchoolDocument->getKey()],
            $metrics->documents($this->admin)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$teacherDocument->getKey(), $firstSchoolDocument->getKey()],
            $metrics->documents($this->schoolAdmin)->pluck('id')->all(),
        );
        $this->assertSame(
            [$teacherDocument->getKey()],
            $metrics->documents($this->teacher)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$teacherApproval->getKey(), $firstSchoolApproval->getKey()],
            $metrics->approvals($this->schoolAdmin)->pluck('id')->all(),
        );
        $this->assertSame(
            [$teacherApproval->getKey()],
            $metrics->approvals($this->teacher)->pluck('id')->all(),
        );
        $this->assertFalse(
            $metrics->approvals($this->schoolAdmin)->whereKey($secondSchoolApproval->getKey())->exists(),
        );
    }

    public function test_dashboard_widgets_render_real_scoped_data_and_respect_permissions(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        Livewire::test(OrganizationStatsWidget::class)
            ->assertDontSee('Total Yayasan')
            ->assertSee('Total Sekolah/Madrasah')
            ->assertSee('Guru dan Pegawai')
            ->assertSee('Pengguna Aktif');

        $this->assertTrue(ApprovalStatsWidget::canView());
        $this->assertTrue(RecentApprovalsWidget::canView());
        $this->assertTrue(RecentActivitiesWidget::canView());

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->schoolAdmin);

        Livewire::test(OrganizationStatsWidget::class)
            ->assertSee($this->firstSchool->name)
            ->assertDontSee($this->secondSchool->name);

        $this->assertFalse(ApprovalStatsWidget::canView());
        $this->assertFalse(RecentApprovalsWidget::canView());
        $this->assertFalse(RecentActivitiesWidget::canView());
    }

    public function test_admin_and_operational_dashboard_pages_are_accessible(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Admin Induk')
            ->assertSee('Grafik Pendapatan')
            ->assertSee('5 Pembayaran Terakhir')
            ->assertSee('Ringkasan Pengajuan Administrasi')
            ->assertSee('Pengajuan SK Terbaru')
            ->assertSee('Perbaikan SK Terbaru');

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->schoolAdmin)
            ->get(route('filament.app.pages.dashboard'))
            ->assertOk();
    }

    public function test_assignment_counts_are_global_for_parent_admin_and_school_scoped_for_school_admin(): void
    {
        $classTeacher = EmployeePosition::factory()->create(['name' => 'Mengajar Guru Kelas']);
        $schoolTwoPosition = EmployeePosition::factory()->create(['name' => 'Mengajar Mapel IPA']);

        foreach ([$this->teacherEmployee, $this->firstSchoolEmployee] as $employee) {
            EmployeeAssignment::factory()->create([
                'employee_id' => $employee->getKey(),
                'employee_position_id' => $classTeacher->getKey(),
                'foundation_id' => $this->firstFoundation->getKey(),
                'school_id' => $this->firstSchool->getKey(),
                'status' => EmployeeAssignment::STATUS_ACTIVE,
            ]);
        }
        EmployeeAssignment::factory()->create([
            'employee_id' => $this->secondSchoolEmployee->getKey(),
            'employee_position_id' => $schoolTwoPosition->getKey(),
            'foundation_id' => $this->secondFoundation->getKey(),
            'school_id' => $this->secondSchool->getKey(),
            'status' => EmployeeAssignment::STATUS_ACTIVE,
        ]);

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertSee('Jumlah Guru/Pegawai Berdasarkan Ketugasan')
            ->assertSee('Cari ketugasan, sekolah atau nama guru...')
            ->assertSee('Jenis Ketugasan')
            ->assertSee('Mengajar Guru Kelas')
            ->assertSee('2 orang')
            ->assertSee('Guru dengan Akun')
            ->assertSee('Pegawai Sekolah Pertama')
            ->assertSee('Mengajar Mapel IPA');

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->schoolAdmin)
            ->get(route('filament.app.pages.dashboard'))
            ->assertOk()
            ->assertSee('Jumlah Guru/Pegawai per Ketugasan')
            ->assertSee('Mengajar Guru Kelas')
            ->assertSee('2 orang')
            ->assertDontSee('Mengajar Mapel IPA')
            ->assertDontSee($this->secondSchool->name);
    }

    public function test_school_dashboard_matches_reference_sections_with_real_scoped_data(): void
    {
        $this->firstSchool->update([
            'email' => 'sekolah-pertama@example.test',
            'accreditation_status' => 'B',
            'land_status' => 'Wakaf',
            'address' => 'Jalan Madrasah Pertama',
        ]);
        StudentEnrollment::query()->create([
            'school_id' => $this->firstSchool->getKey(),
            'academic_year' => StudentEnrollment::currentAcademicYear(),
            'k1' => 10,
            'k2' => 8,
            'submitted_by' => $this->schoolAdmin->getKey(),
        ]);
        StudentEnrollment::query()->create([
            'school_id' => $this->secondSchool->getKey(),
            'academic_year' => StudentEnrollment::currentAcademicYear(),
            'k1' => 99,
            'submitted_by' => $this->admin->getKey(),
        ]);
        EmployeeActivityRequest::query()->create([
            'employee_id' => $this->firstSchoolEmployee->getKey(),
            'employee_name' => 'Pegawai Sekolah Pertama',
            'school_id' => $this->firstSchool->getKey(),
            'school_name' => $this->firstSchool->name,
            'inactive_date' => '2026-08-07',
            'request_letter_path' => 'testing/aktivitas.pdf',
            'status' => EmployeeActivityRequest::STATUS_COMPLETED,
            'submitted_by' => $this->schoolAdmin->getKey(),
        ]);

        filament()->setCurrentPanel(filament()->getPanel('app'));

        $this->actingAs($this->schoolAdmin)
            ->get(route('filament.app.pages.dashboard'))
            ->assertOk()
            ->assertSee('WELCOME TO')
            ->assertSee('Dashboard SiDIKMa-GK')
            ->assertSee('Total Siswa')
            ->assertSee('Guru/Pegawai')
            ->assertSee('Total Akun Internal')
            ->assertSee('Akreditasi')
            ->assertSee('Informasi Madrasah/Sekolah')
            ->assertSee('Ringkasan Rombel')
            ->assertSee('Guru/Pegawai Se-Madrasah')
            ->assertSee('Aktivitas Terbaru')
            ->assertSee($this->firstSchool->name)
            ->assertSee('sekolah-pertama@example.test')
            ->assertSee('Pegawai Sekolah Pertama')
            ->assertDontSee($this->secondSchool->name)
            ->assertDontSee($this->secondSchoolEmployee->name);
    }

    public function test_recent_widgets_only_show_records_available_to_admin(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);
        $approval = ApprovalRequest::factory()->create([
            'approvable_type' => Employee::class,
            'approvable_id' => $this->teacherEmployee->getKey(),
        ]);
        $activity = Activity::query()->latest('id')->firstOrFail();

        Livewire::test(RecentApprovalsWidget::class)
            ->assertCanSeeTableRecords([$approval]);

        Livewire::test(RecentActivitiesWidget::class)
            ->assertCanSeeTableRecords([$activity]);
    }

    /** @return array{Foundation, School} */
    private function createOrganization(string $suffix, string $npsn, string $level): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-DASH-{$suffix}",
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Sekolah {$suffix}",
            'npsn' => $npsn,
            'school_level' => $level,
            'is_active' => true,
        ]);

        return [$foundation, $school];
    }

    private function assignMembership(User $user, Foundation $foundation, School $school): Membership
    {
        return Membership::query()->create([
            'user_id' => $user->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
    }

    private function createDocument(Employee $owner, string $name): Document
    {
        return Document::factory()->create([
            'owner_type' => Employee::class,
            'owner_id' => $owner->getKey(),
            'original_name' => $name,
        ]);
    }
}
