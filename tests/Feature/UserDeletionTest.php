<?php

namespace Tests\Feature;

use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_memberships_are_removed_when_their_user_is_deleted(): void
    {
        $foundation = Foundation::factory()->create();
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI Uji Hapus User',
            'npsn' => '14082026',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $membership = Membership::query()->create([
            'user_id' => $user->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->getKey()]);
        $this->assertDatabaseMissing('memberships', ['id' => $membership->getKey()]);
    }
}
