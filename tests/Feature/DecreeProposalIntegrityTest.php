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
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DecreeProposalIntegrityTest extends TestCase
{
    public function test_sqlite_approval_uses_only_available_writable_user_columns(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeCandidateProposal($foundation, $school, $position, $adminInduk);
        $this->putRequiredDocuments($proposal);

        /** @var Builder $schema */
        $schema = Schema::connection('sidikma_induk');
        $columns = collect($schema->getColumns('users'))->keyBy('name');
        $this->assertArrayHasKey('name', $columns->all());
        $this->assertArrayNotHasKey('nama_lengkap', $columns->all());
        $this->assertFalse(filled($columns->get('name')['generation'] ?? null));

        $result = app(DecreeProposalApprovalService::class)->approve($proposal, $adminInduk);
        $createdUser = User::on('sidikma_induk')->where('email', $proposal->candidate_email)->firstOrFail();

        $this->assertFalse($result['reused_existing']);
        $this->assertSame($proposal->candidate_name, $createdUser->name);
        $this->assertSame(DecreeProposal::STATUS_COMPLETED, $proposal->fresh()->status);
    }

    public function test_user_and_employee_identity_conflicts_abort_without_creating_master_data(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();

        $existingUser = User::factory()->create(['email' => 'integrity-existing-user@example.test']);
        $userConflict = $this->makeCandidateProposal(
            $foundation,
            $school,
            $position,
            $adminInduk,
            ['email' => $existingUser->email],
        );
        $this->putRequiredDocuments($userConflict);
        $beforeUserConflict = $this->masterCounts();

        $this->assertApprovalValidationFailure($userConflict, $adminInduk);
        $this->assertSame($beforeUserConflict, $this->masterCounts());
        $this->assertNull($userConflict->fresh()->employee_id);

        $employeeUser = User::factory()->create(['email' => 'integrity-existing-employee@example.test']);
        $existingEmployee = Employee::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'user_id' => $employeeUser->getKey(),
            'employee_code' => 'INTEGRITY-DUPLICATE-CODE',
            'name' => 'Existing Integrity Employee',
            'employee_type' => Employee::TYPE_GURU,
            'employment_status' => 'GTY',
            'last_education' => 'S1',
            'program_study' => 'Pendidikan',
            'birth_place' => 'Yogyakarta',
            'birth_date' => '1990-01-01',
            'phone' => '081234567890',
            'email' => $employeeUser->email,
            'is_active' => true,
        ]);
        $employeeConflict = $this->makeCandidateProposal(
            $foundation,
            $school,
            $position,
            $adminInduk,
            ['employee_code' => $existingEmployee->employee_code],
        );
        $this->putRequiredDocuments($employeeConflict);
        $beforeEmployeeConflict = $this->masterCounts();

        $this->assertApprovalValidationFailure($employeeConflict, $adminInduk);
        $this->assertSame($beforeEmployeeConflict, $this->masterCounts());
        $this->assertNull($employeeConflict->fresh()->employee_id);
    }

    public function test_invalid_or_incomplete_employee_relation_aborts_without_repairing_master_data(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();

        $orphanUser = User::factory()->create(['email' => 'integrity-orphan@example.test']);
        $orphanEmployee = Employee::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'user_id' => null,
            'employee_code' => 'INTEGRITY-ORPHAN',
            'name' => 'Orphan Integrity Employee',
            'employee_type' => Employee::TYPE_GURU,
            'employment_status' => 'GTY',
            'last_education' => 'S1',
            'program_study' => 'Pendidikan',
            'birth_place' => 'Yogyakarta',
            'birth_date' => '1990-01-01',
            'phone' => '081234567890',
            'email' => $orphanUser->email,
            'is_active' => true,
        ]);
        $orphanProposal = $this->makeCandidateProposal(
            $foundation,
            $school,
            $position,
            $adminInduk,
            ['employee_id' => $orphanEmployee->getKey(), 'email' => $orphanEmployee->email, 'employee_code' => $orphanEmployee->employee_code],
        );
        $this->putRequiredDocuments($orphanProposal);
        $beforeOrphan = $this->masterCounts();

        $this->assertApprovalValidationFailure($orphanProposal, $adminInduk);
        $this->assertSame($beforeOrphan, $this->masterCounts());
        $this->assertSame($orphanEmployee->getKey(), $orphanProposal->fresh()->employee_id);
        $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $orphanProposal->fresh()->status);

        $otherSchool = School::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'Integrity Other School',
            'npsn' => (string) random_int(10000000, 99999999),
            'school_level' => 'SMA',
            'is_active' => true,
        ]);
        $mismatchUser = User::factory()->create(['email' => 'integrity-mismatch@example.test']);
        $mismatchEmployee = Employee::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $otherSchool->getKey(),
            'user_id' => $mismatchUser->getKey(),
            'employee_code' => 'INTEGRITY-MISMATCH',
            'name' => 'Mismatch Integrity Employee',
            'employee_type' => Employee::TYPE_GURU,
            'employment_status' => 'GTY',
            'last_education' => 'S1',
            'program_study' => 'Pendidikan',
            'birth_place' => 'Yogyakarta',
            'birth_date' => '1990-01-01',
            'phone' => '081234567890',
            'email' => $mismatchUser->email,
            'is_active' => true,
        ]);
        $mismatchProposal = $this->makeCandidateProposal(
            $foundation,
            $school,
            $position,
            $adminInduk,
            ['employee_id' => $mismatchEmployee->getKey(), 'email' => $mismatchEmployee->email, 'employee_code' => $mismatchEmployee->employee_code],
        );
        $this->putRequiredDocuments($mismatchProposal);
        $beforeMismatch = $this->masterCounts();

        $this->assertApprovalValidationFailure($mismatchProposal, $adminInduk);
        $this->assertSame($beforeMismatch, $this->masterCounts());
        $this->assertSame($mismatchEmployee->getKey(), $mismatchProposal->fresh()->employee_id);
    }

    public function test_failure_after_user_insert_rolls_back_entire_approval_transaction(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeCandidateProposal($foundation, $school, $position, $adminInduk);
        $this->putRequiredDocuments($proposal);
        $targetEmail = $proposal->candidate_email;
        $before = $this->masterCounts();

        Event::listen('eloquent.created: '.User::class, function (User $user) use ($targetEmail): void {
            if ($user->email === $targetEmail) {
                throw new \RuntimeException('Synthetic failure after User insert.');
            }
        });

        $caught = false;
        try {
            app(DecreeProposalApprovalService::class)->approve($proposal, $adminInduk);
        } catch (\RuntimeException $exception) {
            $caught = true;
        } finally {
            Event::forget('eloquent.created: '.User::class);
        }

        $this->assertTrue($caught, 'Kegagalan sintetis setelah insert User tidak terjadi.');
        $this->assertSame($before, $this->masterCounts());
        $fresh = $proposal->fresh();
        $this->assertNull($fresh->employee_id);
        $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $fresh->status);
    }

    public function test_repeated_approval_does_not_duplicate_records(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeCandidateProposal($foundation, $school, $position, $adminInduk);
        $this->putRequiredDocuments($proposal);
        app(DecreeProposalApprovalService::class)->approve($proposal, $adminInduk);
        $afterFirstApproval = $this->masterCounts();
        $first = $proposal->fresh();

        $this->assertApprovalValidationFailure($first, $adminInduk);

        $this->assertSame($afterFirstApproval, $this->masterCounts());
        $this->assertSame(DecreeProposal::STATUS_COMPLETED, $proposal->fresh()->status);
    }

    private function assertApprovalValidationFailure(DecreeProposal $proposal, User $actor): void
    {
        try {
            app(DecreeProposalApprovalService::class)->approve($proposal, $actor);
            $this->fail('Approval seharusnya dihentikan oleh validasi integritas.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }

    /** @return array{0: Foundation, 1: School, 2: EmployeePosition, 3: User} */
    private function makeContext(): array
    {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $foundation = Foundation::query()->create([
            'name' => "Integrity Foundation {$token}",
            'code' => "INT-{$token}",
            'is_active' => true,
        ]);
        $school = School::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Integrity School {$token}",
            'npsn' => (string) random_int(10000000, 99999999),
            'school_level' => 'SMA',
            'is_active' => true,
        ]);
        $position = EmployeePosition::on('sidikma_induk')->create([
            'code' => "INT-POS-{$token}",
            'name' => "Integrity Position {$token}",
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'is_active' => true,
        ]);
        $adminInduk = User::factory()->create(['email' => "integrity-admin-{$token}@example.test"]);
        $adminInduk->assignRole(User::ROLE_ADMIN_INDUK);

        return [$foundation, $school, $position, $adminInduk];
    }

    /** @param array<string, mixed> $overrides */
    private function makeCandidateProposal(
        Foundation $foundation,
        School $school,
        EmployeePosition $position,
        User $submittedBy,
        array $overrides = [],
    ): DecreeProposal {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $snapshot = [
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'school_name' => $school->name,
            'employee_code' => "INT-CANDIDATE-{$token}",
            'name' => "Integrity Candidate {$token}",
            'email' => "integrity-candidate-{$token}@example.test",
            'phone' => '081234567890',
            'employment_status' => 'GTY',
            'nip' => null,
            'rank' => null,
            'grade' => null,
            'birth_date' => '1990-01-01',
            'birth_place' => 'Yogyakarta',
            'position_id' => $position->getKey(),
            'position_name' => $position->name,
            'assignment_start_date' => '2020-01-01',
            'last_education' => 'S1',
            'nuptk' => null,
            'program_study' => 'Pendidikan',
        ];
        $attributes = [
            'employee_id' => null,
            'candidate_name' => $snapshot['name'],
            'candidate_email' => $snapshot['email'],
            'candidate_phone' => $snapshot['phone'],
            'candidate_school' => $school->name,
            'legacy_snapshot' => $snapshot,
            'status' => DecreeProposal::STATUS_SUBMITTED,
            'notes' => json_encode(['initial' => 'Integrity initial note'], JSON_THROW_ON_ERROR),
            'submitted_by' => $submittedBy->getKey(),
        ];

        foreach (['employee_id', 'email', 'employee_code'] as $key) {
            if (! array_key_exists($key, $overrides)) {
                continue;
            }
            if ($key === 'email') {
                $attributes['candidate_email'] = $overrides[$key];
                $snapshot['email'] = $overrides[$key];
            } elseif ($key === 'employee_code') {
                $snapshot['employee_code'] = $overrides[$key];
            } else {
                $attributes[$key] = $overrides[$key];
            }
        }
        $attributes['legacy_snapshot'] = $snapshot;

        return DecreeProposal::on('sidikma_induk')->create($attributes);
    }

    private function putRequiredDocuments(DecreeProposal $proposal): void
    {
        $paths = [
            'photo_path' => "integrity/{$proposal->getKey()}-photo.jpg",
            'diploma_path' => "integrity/{$proposal->getKey()}-diploma.pdf",
            'application_letter_path' => "integrity/{$proposal->getKey()}-application.pdf",
            'service_statement_path' => "integrity/{$proposal->getKey()}-statement.pdf",
            'task_assignment_certificate_path' => "integrity/{$proposal->getKey()}-task.pdf",
        ];
        foreach ($paths as $path) {
            Storage::disk('decree-proposals')->put($path, 'synthetic integrity document');
        }
        $proposal->forceFill($paths)->save();
    }

    /** @return array{users: int, employees: int, memberships: int, assignments: int} */
    private function masterCounts(): array
    {
        return [
            'users' => User::on('sidikma_induk')->count(),
            'employees' => Employee::on('sidikma_induk')->withTrashed()->count(),
            'memberships' => Membership::on('sidikma_induk')->count(),
            'assignments' => EmployeeAssignment::on('sidikma_induk')->withTrashed()->count(),
        ];
    }
}
