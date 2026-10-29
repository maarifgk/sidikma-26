<?php

namespace Tests\Unit;

use App\Models\CorrespondenceRequest;
use App\Models\EmployeeActivityRequest;
use App\Models\EmployeeMutation;
use App\Models\ProposalRequest;
use App\Models\SipinterUpdate;
use App\Models\User;
use App\Policies\CorrespondenceRequestPolicy;
use App\Policies\EmployeeActivityRequestPolicy;
use App\Policies\EmployeeMutationPolicy;
use App\Policies\ProposalRequestPolicy;
use App\Policies\SipinterUpdatePolicy;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AdministrationPolicyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_admin_induk_keeps_access_with_permission_even_without_school_id(): void
    {
        $user = $this->user(adminInduk: true, schoolIds: [], can: true);

        $this->assertTrue((new CorrespondenceRequestPolicy)->view($user, new CorrespondenceRequest(['school_id' => null])));
        $this->assertTrue((new EmployeeActivityRequestPolicy)->view($user, new EmployeeActivityRequest(['school_id' => null])));
        $this->assertTrue((new EmployeeMutationPolicy)->view($user, new EmployeeMutation(['origin_school_id' => null, 'destination_school_id' => null])));
        $this->assertTrue((new ProposalRequestPolicy)->view($user, new ProposalRequest(['school_id' => null])));
        $this->assertTrue((new SipinterUpdatePolicy)->view($user, new SipinterUpdate(['school_id' => null])));
    }

    public function test_school_admin_can_access_only_records_owned_by_an_accessible_school(): void
    {
        $user = $this->user(adminInduk: false, schoolIds: [10], can: true);

        $this->assertScopedRecord(new CorrespondenceRequestPolicy, $user, new CorrespondenceRequest(['school_id' => 10]), new CorrespondenceRequest(['school_id' => 99]), new CorrespondenceRequest(['school_id' => null]));
        $this->assertScopedRecord(new EmployeeActivityRequestPolicy, $user, new EmployeeActivityRequest(['school_id' => 10]), new EmployeeActivityRequest(['school_id' => 99]), new EmployeeActivityRequest(['school_id' => null]));
        $this->assertScopedRecord(new ProposalRequestPolicy, $user, new ProposalRequest(['school_id' => 10]), new ProposalRequest(['school_id' => 99]), new ProposalRequest(['school_id' => null]));
        $this->assertScopedRecord(new SipinterUpdatePolicy, $user, new SipinterUpdate(['school_id' => 10]), new SipinterUpdate(['school_id' => 99]), new SipinterUpdate(['school_id' => null]));
    }

    public function test_school_admin_cannot_access_mutation_without_a_valid_owned_school(): void
    {
        $user = $this->user(adminInduk: false, schoolIds: [10], can: true);
        $policy = new EmployeeMutationPolicy;

        $owned = new EmployeeMutation(['origin_school_id' => 10, 'destination_school_id' => null]);
        $otherSchool = new EmployeeMutation(['origin_school_id' => 99, 'destination_school_id' => 99]);
        $empty = new EmployeeMutation(['origin_school_id' => null, 'destination_school_id' => null, 'submitted_by' => 1]);

        $this->assertTrue($policy->view($user, $owned));
        $this->assertFalse($policy->view($user, $otherSchool));
        $this->assertFalse($policy->view($user, $empty));
        $this->assertFalse($policy->update($user, $otherSchool));
        $this->assertFalse($policy->delete($user, $empty));
    }

    public function test_permission_is_still_required_for_school_scoped_actions(): void
    {
        $user = $this->user(adminInduk: false, schoolIds: [10], can: false);
        $record = new EmployeeActivityRequest(['school_id' => 10]);

        $this->assertFalse((new EmployeeActivityRequestPolicy)->view($user, $record));
        $this->assertFalse((new EmployeeActivityRequestPolicy)->update($user, $record));
        $this->assertFalse((new EmployeeActivityRequestPolicy)->delete($user, $record));
    }

    private function assertScopedRecord(
        object $policy,
        User $user,
        object $owned,
        object $otherSchool,
        object $empty,
    ): void {
        $this->assertTrue($policy->view($user, $owned));
        $this->assertTrue($policy->update($user, $owned));
        $this->assertTrue($policy->delete($user, $owned));
        $this->assertFalse($policy->view($user, $otherSchool));
        $this->assertFalse($policy->update($user, $otherSchool));
        $this->assertFalse($policy->delete($user, $otherSchool));
        $this->assertFalse($policy->view($user, $empty));
        $this->assertFalse($policy->update($user, $empty));
        $this->assertFalse($policy->delete($user, $empty));
    }

    private function user(bool $adminInduk, array $schoolIds, bool $can): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isAdminInduk')->andReturn($adminInduk);
        $user->shouldReceive('accessibleSchoolIds')->andReturn(new Collection($schoolIds));
        $user->shouldReceive('can')->andReturn($can);

        return $user;
    }
}
