<?php

namespace Tests\Feature;

use App\Filament\Resources\Foundations\FoundationResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);
        $this->travelTo('2026-08-07 12:00:00');
    }

    public function test_admin_induk_has_full_foundation_and_school_access(): void
    {
        [$foundation, $school] = $this->createOrganization('INDUK', '11111111');
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $this->assertTrue($admin->can('viewAny', Foundation::class));
        $this->assertTrue($admin->can('view', $foundation));
        $this->assertTrue($admin->can('create', Foundation::class));
        $this->assertTrue($admin->can('update', $foundation));
        $this->assertTrue($admin->can('delete', $foundation));
        $this->assertTrue($admin->can('viewAny', School::class));
        $this->assertTrue($admin->can('view', $school));
        $this->assertTrue($admin->can('create', School::class));
        $this->assertTrue($admin->can('update', $school));
        $this->assertTrue($admin->can('delete', $school));

        $this->assertTrue(FoundationResource::canCreate());
        $this->assertTrue(FoundationResource::canEdit($foundation));
        $this->assertTrue(FoundationResource::canDelete($foundation));
        $this->assertTrue(SchoolResource::canCreate());
        $this->assertTrue(SchoolResource::canEdit($school));
        $this->assertTrue(SchoolResource::canDelete($school));
        $this->assertTrue(SchoolResource::canDeleteAny());
    }

    public function test_school_admin_can_only_view_and_update_its_assigned_school(): void
    {
        [$assignedFoundation, $assignedSchool] = $this->createOrganization('TUGAS', '22222222');
        [$otherFoundation, $otherSchool] = $this->createOrganization('LAIN', '33333333');
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->assignMembership($schoolAdmin, $assignedFoundation, $assignedSchool);
        $this->actingAs($schoolAdmin);

        $this->assertTrue($schoolAdmin->can('viewAny', Foundation::class));
        $this->assertTrue($schoolAdmin->can('view', $assignedFoundation));
        $this->assertFalse($schoolAdmin->can('view', $otherFoundation));
        $this->assertFalse($schoolAdmin->can('create', Foundation::class));
        $this->assertFalse($schoolAdmin->can('update', $assignedFoundation));

        $this->assertTrue($schoolAdmin->can('viewAny', School::class));
        $this->assertTrue($schoolAdmin->can('view', $assignedSchool));
        $this->assertTrue($schoolAdmin->can('update', $assignedSchool));
        $this->assertFalse($schoolAdmin->can('view', $otherSchool));
        $this->assertFalse($schoolAdmin->can('update', $otherSchool));
        $this->assertFalse($schoolAdmin->can('create', School::class));
        $this->assertFalse($schoolAdmin->can('delete', $assignedSchool));

        $this->assertFalse(SchoolResource::canCreate());
        $this->assertTrue(SchoolResource::canEdit($assignedSchool));
        $this->assertFalse(SchoolResource::canEdit($otherSchool));
        $this->assertFalse(SchoolResource::canDelete($assignedSchool));
        $this->assertFalse(SchoolResource::canDeleteAny());
    }

    public function test_teacher_can_only_view_its_assigned_foundation_and_school(): void
    {
        [$assignedFoundation, $assignedSchool] = $this->createOrganization('GURU', '44444444');
        [, $otherSchool] = $this->createOrganization('LAIN', '55555555');
        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->assignMembership($teacher, $assignedFoundation, $assignedSchool);
        $this->actingAs($teacher);

        $this->assertTrue($teacher->can('view', $assignedFoundation));
        $this->assertTrue($teacher->can('viewAny', School::class));
        $this->assertTrue($teacher->can('view', $assignedSchool));
        $this->assertFalse($teacher->can('view', $otherSchool));
        $this->assertFalse($teacher->can('create', School::class));
        $this->assertFalse($teacher->can('update', $assignedSchool));
        $this->assertFalse($teacher->can('delete', $assignedSchool));

        $this->assertFalse(SchoolResource::canCreate());
        $this->assertFalse(SchoolResource::canEdit($assignedSchool));
        $this->assertFalse(SchoolResource::canDelete($assignedSchool));
    }

    public function test_expired_membership_does_not_authorize_record_access(): void
    {
        [$foundation, $school] = $this->createOrganization('KEDALUWARSA', '66666666');
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
            'end_date' => '2026-08-06',
        ]);

        $this->assertFalse($schoolAdmin->can('view', $foundation));
        $this->assertFalse($schoolAdmin->can('view', $school));
        $this->assertFalse($schoolAdmin->can('update', $school));
    }

    /**
     * @return array{Foundation, School}
     */
    private function createOrganization(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-POLICY-{$suffix}",
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
}
