<?php

namespace Tests\Feature;

use App\Filament\Resources\Schools\SchoolResource;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolUrlAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);
        $this->travelTo('2026-08-07 12:00:00');
    }

    public function test_admin_induk_is_redirected_from_operational_school_urls_to_admin_panel(): void
    {
        [, $firstSchool] = $this->createOrganization('SATU', '11111111');
        [, $secondSchool] = $this->createOrganization('DUA', '22222222');
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $this->get($this->editUrl($firstSchool, 'admin'))->assertOk();
        $this->get($this->editUrl($secondSchool, 'admin'))->assertOk();
        $this->get($this->editUrl($firstSchool, 'app'))->assertRedirect('/admin');
        $this->get($this->editUrl($secondSchool, 'app'))->assertRedirect('/admin');
    }

    public function test_school_admin_cannot_open_school_urls_on_admin_panel(): void
    {
        [$assignedFoundation, $assignedSchool] = $this->createOrganization('TUGAS', '33333333');
        [, $otherSchool] = $this->createOrganization('LAIN', '44444444');
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->assignMembership($schoolAdmin, $assignedFoundation, $assignedSchool);
        $this->actingAs($schoolAdmin);

        $this->get($this->editUrl($assignedSchool, 'admin'))->assertForbidden();
        $this->get($this->editUrl($otherSchool, 'admin'))->assertForbidden();
        $this->get($this->editUrl($assignedSchool, 'app'))->assertOk();
        $this->get($this->editUrl($otherSchool, 'app'))->assertNotFound();
    }

    public function test_teacher_cannot_open_edit_url_for_assigned_or_other_school(): void
    {
        [$assignedFoundation, $assignedSchool] = $this->createOrganization('GURU', '55555555');
        [, $otherSchool] = $this->createOrganization('LAIN', '66666666');
        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->assignMembership($teacher, $assignedFoundation, $assignedSchool);
        $this->actingAs($teacher);

        $this->get($this->editUrl($assignedSchool, 'admin'))->assertForbidden();
        $this->get($this->editUrl($otherSchool, 'admin'))->assertForbidden();
        $this->get($this->editUrl($assignedSchool, 'app'))->assertForbidden();
        $this->get($this->editUrl($otherSchool, 'app'))->assertNotFound();
    }

    public function test_expired_membership_cannot_open_school_edit_url(): void
    {
        [$foundation, $school] = $this->createOrganization('KEDALUWARSA', '77777777');
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
            'end_date' => '2026-08-06',
        ]);

        $this->actingAs($schoolAdmin);

        $this->get($this->editUrl($school, 'admin'))->assertForbidden();
        $this->get($this->editUrl($school, 'app'))->assertNotFound();
    }

    public function test_guest_is_redirected_to_the_correct_panel_login(): void
    {
        [, $school] = $this->createOrganization('TAMU', '88888888');

        $this->get($this->editUrl($school, 'admin'))
            ->assertRedirect('/admin/login');
        $this->get($this->editUrl($school, 'app'))
            ->assertRedirect('/app/login');
    }

    /**
     * @return array{Foundation, School}
     */
    private function createOrganization(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-URL-{$suffix}",
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

    private function editUrl(School $school, string $panel): string
    {
        return SchoolResource::getUrl(
            'edit',
            ['record' => $school],
            isAbsolute: false,
            panel: $panel,
        );
    }
}
