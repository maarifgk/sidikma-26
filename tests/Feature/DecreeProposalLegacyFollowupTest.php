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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DecreeProposalLegacyFollowupTest extends TestCase
{
    public function test_new_nonlegacy_approval_creates_one_complete_master_record_set(): void
    {
        Storage::fake('decree-proposals');
        DB::connection('sqlite')->beginTransaction();

        try {
            $token = strtolower(bin2hex(random_bytes(6)));
            // Reuse committed organization fixtures; only candidate/master records
            // are synthetic and remain inside the rollback transaction.
            $school = School::on('sqlite')->firstOrFail();
            $foundation = Foundation::on('sqlite')->findOrFail($school->foundation_id);
            $position = EmployeePosition::on('sqlite')->where('is_active', true)->firstOrFail();
            $admin = User::factory()->create(['email' => 'admin-'.$token.'@example.test']);
            $admin->assignRole(User::ROLE_ADMIN_INDUK);

            $paths = [
                'photo_path' => 'synthetic/'.$token.'/photo.jpg',
                'diploma_path' => 'synthetic/'.$token.'/diploma.pdf',
                'application_letter_path' => 'synthetic/'.$token.'/application.pdf',
                'service_statement_path' => 'synthetic/'.$token.'/statement.pdf',
                'task_assignment_certificate_path' => 'synthetic/'.$token.'/assignment.pdf',
            ];
            foreach ($paths as $path) {
                Storage::disk('decree-proposals')->put($path, 'synthetic');
            }

            $proposal = DecreeProposal::on('sqlite')->create([
                'legacy_proposal_id' => null,
                'candidate_name' => 'Guru Baru '.$token,
                'candidate_email' => 'guru-'.$token.'@example.test',
                'candidate_phone' => '081230'.$token,
                'candidate_school' => $school->name,
                'status' => DecreeProposal::STATUS_SUBMITTED,
                'submitted_by' => $admin->getKey(),
                'legacy_snapshot' => [
                    'foundation_id' => $foundation->getKey(),
                    'school_id' => $school->getKey(),
                    'school_name' => $school->name,
                    'employee_code' => 'EW-'.$token,
                    'name' => 'Guru Baru '.$token,
                    'email' => 'guru-'.$token.'@example.test',
                    'phone' => '081230'.$token,
                    'employment_status' => 'GTY',
                    'position_id' => $position->getKey(),
                    'position_name' => $position->name,
                    'assignment_start_date' => '2026-01-01',
                    'last_education' => 'S1',
                    'program_study' => 'Pendidikan',
                    'birth_place' => 'Gunungkidul',
                    'birth_date' => '1990-01-01',
                    'nuptk' => null,
                ],
                ...$paths,
            ]);

            $before = [
                User::on('sqlite')->count(),
                Employee::on('sqlite')->count(),
                Membership::on('sqlite')->count(),
                EmployeeAssignment::on('sqlite')->count(),
            ];
            $result = app(DecreeProposalApprovalService::class)->approve($proposal, $admin);
            $fresh = DecreeProposal::on('sqlite')->findOrFail($proposal->getKey());
            $employee = Employee::on('sqlite')->findOrFail($fresh->employee_id);

            $this->assertFalse($proposal->isHistoricalLegacy());
            $this->assertSame(DecreeProposal::STATUS_COMPLETED, $fresh->status);
            $this->assertSame(1, User::on('sqlite')->whereKey($result['user']->getKey())->count());
            $this->assertSame($before[0] + 1, User::on('sqlite')->count());
            $this->assertSame($before[1] + 1, Employee::on('sqlite')->count());
            $this->assertSame($before[2] + 1, Membership::on('sqlite')->count());
            $this->assertSame($before[3] + 1, EmployeeAssignment::on('sqlite')->count());
            $this->assertSame(1, Membership::on('sqlite')->where('user_id', $employee->user_id)->count());
            $this->assertSame(1, EmployeeAssignment::on('sqlite')->where('employee_id', $employee->getKey())->count());
            $this->assertNull($employee->nuptk);
            if (Schema::connection('sqlite')->hasColumn('users', 'nuptk')) {
                $this->assertSame('0', (string) User::on('sqlite')->findOrFail($employee->user_id)->nuptk);
            }
        } finally {
            DB::connection('sqlite')->rollBack();
        }
    }
}
