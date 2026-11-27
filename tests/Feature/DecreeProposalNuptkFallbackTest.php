<?php

namespace Tests\Feature;

use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use App\Services\DecreeProposalApprovalService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DecreeProposalNuptkFallbackTest extends TestCase
{
    public function test_two_distinct_candidates_without_nuptk_are_approved_without_false_conflict(): void
    {
        Storage::fake('decree-proposals');

        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $foundation = Foundation::query()->create([
            'name' => "NUPTK Foundation {$token}",
            'code' => "NUP-{$token}",
            'is_active' => true,
        ]);
        $school = School::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "NUPTK School {$token}",
            'npsn' => (string) random_int(10000000, 99999999),
            'school_level' => 'SMA',
            'is_active' => true,
        ]);
        $position = EmployeePosition::on('sidikma_induk')->create([
            'code' => "NUP-POS-{$token}",
            'name' => "NUPTK Position {$token}",
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['email' => "nup-admin-{$token}@example.test"]);
        $admin->assignRole(User::ROLE_ADMIN_INDUK);

        $proposals = [];
        foreach ([['A', "EWANUGK-NUP-A-{$token}", '081230000001'], ['B', "EWANUGK-NUP-B-{$token}", '081230000002']] as [$suffix, $employeeCode, $phone]) {
            $email = "nup-candidate-{$suffix}-{$token}@example.test";
            $paths = [
                'photo_path' => "nuptk/{$token}/{$suffix}/photo.jpg",
                'diploma_path' => "nuptk/{$token}/{$suffix}/diploma.pdf",
                'application_letter_path' => "nuptk/{$token}/{$suffix}/application.pdf",
                'service_statement_path' => "nuptk/{$token}/{$suffix}/statement.pdf",
                'task_assignment_certificate_path' => "nuptk/{$token}/{$suffix}/assignment.pdf",
            ];
            foreach ($paths as $path) {
                Storage::disk('decree-proposals')->put($path, 'synthetic');
            }

            $proposals[] = DecreeProposal::on('sidikma_induk')->create([
                'candidate_name' => "NUPTK Candidate {$suffix} {$token}",
                'candidate_email' => $email,
                'candidate_phone' => $phone,
                'candidate_school' => $school->name,
                'status' => DecreeProposal::STATUS_SUBMITTED,
                'submitted_by' => $admin->getKey(),
                'legacy_snapshot' => [
                    'foundation_id' => $foundation->getKey(),
                    'school_id' => $school->getKey(),
                    'school_name' => $school->name,
                    'employee_code' => $employeeCode,
                    'name' => "NUPTK Candidate {$suffix} {$token}",
                    'email' => $email,
                    'phone' => $phone,
                    'employment_status' => 'GTY',
                    'position_id' => $position->getKey(),
                    'position_name' => $position->name,
                    'assignment_start_date' => '2026-01-01',
                    'last_education' => 'S1',
                    'program_study' => 'Pendidikan',
                    'birth_place' => 'Gunungkidul',
                    'birth_date' => '1990-01-01',
                    'nuptk' => null,
                    'nip' => null,
                ],
                ...$paths,
            ]);
        }

        $results = [];
        foreach ($proposals as $proposal) {
            try {
                $results[] = app(DecreeProposalApprovalService::class)->approve($proposal, $admin);
            } catch (ValidationException $exception) {
                $this->fail(json_encode($exception->errors(), JSON_THROW_ON_ERROR));
            }
        }

        $freshProposals = array_map(
            fn (DecreeProposal $proposal): DecreeProposal => DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey()),
            $proposals,
        );
        $users = array_map(
            fn (array $result): User => User::on('sidikma_induk')->findOrFail($result['user']->getKey()),
            $results,
        );
        $employees = array_map(
            fn (DecreeProposal $proposal): Employee => Employee::on('sidikma_induk')->findOrFail($proposal->employee_id),
            $freshProposals,
        );

        $this->assertCount(2, $results);
        $this->assertSame([DecreeProposal::STATUS_COMPLETED, DecreeProposal::STATUS_COMPLETED], array_map(fn (DecreeProposal $proposal): string => $proposal->status, $freshProposals));
        if (Schema::connection('sidikma_induk')->hasColumn('users', 'nuptk')) {
            $this->assertSame(['0', '0'], array_map(fn (User $user): string => (string) $user->nuptk, $users));
        } else {
            $this->assertFalse(Schema::connection('sidikma_induk')->hasColumn('users', 'nuptk'));
        }
        $this->assertSame([null, null], array_map(fn (Employee $employee): mixed => $employee->nuptk, $employees));
        $this->assertNotSame($users[0]->getKey(), $users[1]->getKey());
        $this->assertNotSame($employees[0]->getKey(), $employees[1]->getKey());
        $this->assertNotSame($users[0]->email, $users[1]->email);
        $this->assertNotSame($employees[0]->employee_code, $employees[1]->employee_code);

        foreach ($employees as $employee) {
            $this->assertSame(1, Membership::on('sidikma_induk')->where('user_id', $employee->user_id)->count());
            $this->assertSame(1, EmployeeAssignment::on('sidikma_induk')->where('employee_id', $employee->getKey())->count());
        }

        $this->assertSame(2, User::on('sidikma_induk')->whereIn('email', array_map(fn (User $user): string => $user->email, $users))->count());
        $this->assertSame(2, Employee::on('sidikma_induk')->whereIn('employee_code', array_map(fn (Employee $employee): string => $employee->employee_code, $employees))->count());
    }
}
