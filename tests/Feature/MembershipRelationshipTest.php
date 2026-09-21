<?php

namespace Tests\Feature;

use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_a_foundation_level_membership(): void
    {
        $user = User::factory()->create();
        $foundation = $this->createFoundation();

        $membership = Membership::query()->create([
            'user_id' => $user->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => null,
            'status' => 'active',
            'start_date' => '2026-07-01',
        ]);

        $this->assertTrue($membership->user->is($user));
        $this->assertTrue($membership->foundation->is($foundation));
        $this->assertNull($membership->school);
        $this->assertTrue($user->memberships->contains($membership));
        $this->assertTrue($user->foundations->contains($foundation));
        $this->assertCount(0, $user->schools);
        $this->assertTrue($foundation->memberships->contains($membership));
        $this->assertSame('2026-07-01', $membership->start_date->toDateString());
    }

    public function test_user_can_have_a_school_level_membership(): void
    {
        $user = User::factory()->create();
        $foundation = $this->createFoundation();
        $school = $this->createSchool($foundation);

        $membership = Membership::query()->create([
            'user_id' => $user->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->assertTrue($membership->user->is($user));
        $this->assertTrue($membership->foundation->is($foundation));
        $this->assertTrue($membership->school->is($school));
        $this->assertTrue($user->memberships->contains($membership));
        $this->assertTrue($user->foundations->contains($foundation));
        $this->assertTrue($user->schools->contains($school));
        $this->assertTrue($foundation->memberships->contains($membership));
        $this->assertTrue($school->memberships->contains($membership));
        $this->assertSame('2027-06-30', $membership->end_date->toDateString());
    }

    private function createFoundation(): Foundation
    {
        return Foundation::query()->create([
            'name' => 'Yayasan Membership',
            'code' => 'YYS-MEMBER',
            'is_active' => true,
        ]);
    }

    private function createSchool(Foundation $foundation): School
    {
        return School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'Madrasah Membership',
            'npsn' => '99887766',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }
}
