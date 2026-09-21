<?php

namespace Tests\Feature;

use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MembershipAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(User::ROLE_ADMIN_INDUK, 'web');
        Role::findOrCreate(User::ROLE_ADMIN_SEKOLAH_MADRASAH, 'web');
        Role::findOrCreate(User::ROLE_GURU_PEGAWAI, 'web');

        $this->travelTo('2026-08-07 12:00:00');
    }

    public function test_admin_induk_can_access_all_foundations_and_schools(): void
    {
        [$firstFoundation, $firstSchool] = $this->createOrganization('SATU', '11111111');
        [$secondFoundation, $secondSchool] = $this->createOrganization('DUA', '22222222');
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);

        $this->assertEqualsCanonicalizing(
            [$firstFoundation->getKey(), $secondFoundation->getKey()],
            $admin->accessibleFoundationIds()->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$firstSchool->getKey(), $secondSchool->getKey()],
            $admin->accessibleSchoolIds()->all(),
        );
        $this->assertCount(2, Foundation::query()->accessibleTo($admin)->get());
        $this->assertCount(2, School::query()->accessibleTo($admin)->get());
    }

    public function test_school_admin_and_teacher_only_access_their_assigned_school(): void
    {
        [$firstFoundation, $firstSchool] = $this->createOrganization('SATU', '11111111');
        [$secondFoundation, $secondSchool] = $this->createOrganization('DUA', '22222222');

        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->assignActiveMembership($schoolAdmin, $firstFoundation, $firstSchool);

        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->assignActiveMembership($teacher, $secondFoundation, $secondSchool);

        $this->assertSame([$firstFoundation->getKey()], $schoolAdmin->accessibleFoundationIds()->all());
        $this->assertSame([$firstSchool->getKey()], $schoolAdmin->accessibleSchoolIds()->all());
        $this->assertTrue(Foundation::query()->accessibleTo($schoolAdmin)->sole()->is($firstFoundation));
        $this->assertTrue(School::query()->accessibleTo($schoolAdmin)->sole()->is($firstSchool));

        $this->assertSame([$secondFoundation->getKey()], $teacher->accessibleFoundationIds()->all());
        $this->assertSame([$secondSchool->getKey()], $teacher->accessibleSchoolIds()->all());
        $this->assertTrue(Foundation::query()->accessibleTo($teacher)->sole()->is($secondFoundation));
        $this->assertTrue(School::query()->accessibleTo($teacher)->sole()->is($secondSchool));
    }

    public function test_inactive_future_and_expired_memberships_do_not_grant_access(): void
    {
        [$foundation, $activeSchool] = $this->createOrganization('AKTIF', '11111111');
        [, $inactiveSchool] = $this->createOrganization('NONAKTIF', '22222222');
        [, $futureSchool] = $this->createOrganization('MASA-DEPAN', '33333333');
        [, $expiredSchool] = $this->createOrganization('KEDALUWARSA', '44444444');
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        $this->assignActiveMembership($user, $foundation, $activeSchool);

        Membership::query()->create([
            'user_id' => $user->getKey(),
            'foundation_id' => $inactiveSchool->foundation_id,
            'school_id' => $inactiveSchool->getKey(),
            'status' => 'inactive',
        ]);

        Membership::query()->create([
            'user_id' => $user->getKey(),
            'foundation_id' => $futureSchool->foundation_id,
            'school_id' => $futureSchool->getKey(),
            'status' => 'active',
            'start_date' => '2026-08-08',
        ]);

        Membership::query()->create([
            'user_id' => $user->getKey(),
            'foundation_id' => $expiredSchool->foundation_id,
            'school_id' => $expiredSchool->getKey(),
            'status' => 'active',
            'end_date' => '2026-08-06',
        ]);

        $this->assertSame([$activeSchool->getKey()], $user->accessibleSchoolIds()->all());
        $this->assertTrue(School::query()->accessibleTo($user)->sole()->is($activeSchool));
        $this->assertCount(1, $user->memberships()->active()->get());
    }

    public function test_membership_does_not_grant_access_without_a_scoped_role(): void
    {
        [$foundation, $school] = $this->createOrganization('TANPA-ROLE', '55555555');
        $user = User::factory()->create();
        $this->assignActiveMembership($user, $foundation, $school);

        $this->assertTrue($user->accessibleFoundationIds()->isEmpty());
        $this->assertTrue($user->accessibleSchoolIds()->isEmpty());
        $this->assertFalse(Foundation::query()->accessibleTo($user)->exists());
        $this->assertFalse(School::query()->accessibleTo($user)->exists());
    }

    /**
     * @return array{Foundation, School}
     */
    private function createOrganization(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-{$suffix}",
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

    private function assignActiveMembership(User $user, Foundation $foundation, School $school): Membership
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
