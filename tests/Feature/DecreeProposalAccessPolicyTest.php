<?php

namespace Tests\Feature;

use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\User;
use App\Policies\DecreeProposalPolicy;
use Mockery;
use Tests\TestCase;

class DecreeProposalAccessPolicyTest extends TestCase
{
    public function test_edit_and_decision_permissions_are_scoped_by_role_status_and_school(): void
    {
        $policy = new DecreeProposalPolicy;
        $adminInduk = Mockery::mock(User::class);
        $adminInduk->shouldReceive('isAdminInduk')->andReturn(true);
        $adminInduk->shouldReceive('can')->with('approval.update')->andReturn(true);

        $adminSekolah = Mockery::mock(User::class);
        $adminSekolah->shouldReceive('isAdminInduk')->andReturn(false);
        $adminSekolah->shouldReceive('hasRole')->with(User::ROLE_ADMIN_SEKOLAH_MADRASAH)->andReturn(true);
        $adminSekolah->shouldReceive('can')->with('approval.view')->andReturn(true);
        $adminSekolah->shouldReceive('can')->with('approval.update')->andReturn(false);
        $adminSekolah->shouldReceive('accessibleSchoolIds')->andReturn(collect([1]));

        $guru = Mockery::mock(User::class);
        $guru->shouldReceive('isAdminInduk')->andReturn(false);
        $guru->shouldReceive('hasRole')->with(User::ROLE_ADMIN_SEKOLAH_MADRASAH)->andReturn(false);
        $guru->shouldReceive('can')->andReturn(false);

        foreach (DecreeProposal::statusOptions() as $status => $_label) {
            $proposal = new DecreeProposal;
            $proposal->forceFill(['status' => $status, 'employee_id' => 123]);
            $proposal->setRelation('employee', (new Employee)->forceFill(['school_id' => 1]));

            $this->assertTrue($policy->edit($adminInduk, $proposal), $status);
            $this->assertTrue($policy->edit($adminSekolah, $proposal), $status);
            $this->assertFalse($policy->updateStatus($adminSekolah, $proposal), $status);
            $this->assertFalse($policy->update($adminSekolah, $proposal), $status);
            $this->assertFalse($policy->edit($guru, $proposal), $status);
        }

        $otherSchoolProposal = new DecreeProposal;
        $otherSchoolProposal->forceFill(['status' => DecreeProposal::STATUS_COMPLETED, 'employee_id' => 456]);
        $otherSchoolProposal->setRelation('employee', (new Employee)->forceFill(['school_id' => 2]));

        $this->assertFalse($policy->edit($adminSekolah, $otherSchoolProposal));
    }
}
