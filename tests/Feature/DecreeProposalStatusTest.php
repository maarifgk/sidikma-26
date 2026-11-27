<?php

namespace Tests\Feature;

use App\Filament\Resources\DecreeProposals\Pages\ListDecreeProposals;
use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use App\Services\DecreeProposalApprovalService;
use App\Services\DecreeProposalStatusService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DecreeProposalStatusTest extends TestCase
{
    public function test_all_status_transitions_preserve_initial_notes_and_store_new_decision(): void
    {
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $transitions = [
            [DecreeProposal::STATUS_SUBMITTED, DecreeProposal::STATUS_COMPLETED],
            [DecreeProposal::STATUS_SUBMITTED, DecreeProposal::STATUS_REJECTED],
            [DecreeProposal::STATUS_REJECTED, DecreeProposal::STATUS_COMPLETED],
            [DecreeProposal::STATUS_REJECTED, DecreeProposal::STATUS_SUBMITTED],
            [DecreeProposal::STATUS_COMPLETED, DecreeProposal::STATUS_REJECTED],
            [DecreeProposal::STATUS_COMPLETED, DecreeProposal::STATUS_SUBMITTED],
        ];

        foreach ($transitions as [$from, $to]) {
            $proposal = $this->makeProposal($foundation, $school, $position, $from, $adminInduk);
            $reason = "Decision {$from} ke {$to}";

            app(DecreeProposalStatusService::class)->update($proposal, $adminInduk, $to, $reason);

            $fresh = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());
            $notes = json_decode((string) $fresh->notes, true);
            $this->assertSame($to, $fresh->status, "$from -> $to tidak tersimpan.");
            $this->assertSame('Catatan awal sintetis', $notes['initial']);
            $this->assertSame($reason, $notes['decision_reason']);
            $this->assertSame($to, $notes['decision_status']);
            $this->assertSame($adminInduk->getKey(), $notes['decision_updated_by']);
            $this->assertNotEmpty($notes['decision_updated_at']);
            if ($to === DecreeProposal::STATUS_REJECTED) {
                $this->assertSame($reason, $notes['rejection_reason']);
            } else {
                $this->assertSame('Alasan lama', $notes['rejection_reason']);
            }
        }
    }

    public function test_changing_reason_without_changing_status_is_persisted(): void
    {
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_COMPLETED, $adminInduk);

        app(DecreeProposalStatusService::class)->update(
            $proposal,
            $adminInduk,
            DecreeProposal::STATUS_COMPLETED,
            'Keterangan completed terbaru',
        );

        $fresh = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());
        $notes = json_decode((string) $fresh->notes, true);
        $this->assertSame(DecreeProposal::STATUS_COMPLETED, $fresh->status);
        $this->assertSame('Keterangan completed terbaru', $notes['decision_reason']);
        $this->assertSame('Catatan awal sintetis', $notes['initial']);
        $this->assertSame('Alasan lama', $notes['rejection_reason']);
    }

    public function test_rejection_requires_reason_and_rolls_back_without_changes(): void
    {
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_SUBMITTED, $adminInduk);
        $beforeNotes = $proposal->notes;

        try {
            app(DecreeProposalStatusService::class)->update(
                $proposal,
                $adminInduk,
                DecreeProposal::STATUS_REJECTED,
                null,
            );
            $this->fail('Penolakan tanpa alasan seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $fresh = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());
        $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $fresh->status);
        $this->assertSame($beforeNotes, $fresh->notes);
    }

    public function test_school_admin_and_role_three_cannot_change_status_backend_or_ui(): void
    {
        [$foundation, $school, $position, $adminInduk, $adminSekolah, $roleThree] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_SUBMITTED, $adminInduk);

        foreach ([$adminSekolah, $roleThree] as $actor) {
            try {
                app(DecreeProposalStatusService::class)->update(
                    $proposal,
                    $actor,
                    DecreeProposal::STATUS_COMPLETED,
                    'Tidak boleh diubah role ini',
                );
                $this->fail('Role non Admin Induk berhasil mengubah status.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }

        filament()->setCurrentPanel(filament()->getPanel('app'));
        Livewire::actingAs($adminSekolah)
            ->test(ListDecreeProposals::class)
            ->mountTableAction('detail', $proposal)
            ->assertActionHidden('updateStatus');
    }

    public function test_filament_update_status_action_persists_status_and_reason(): void
    {
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_SUBMITTED, $adminInduk);

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        Livewire::actingAs($adminInduk)
            ->test(ListDecreeProposals::class)
            ->callTableAction('detail.updateStatus', $proposal, data: [
                'status' => DecreeProposal::STATUS_REJECTED,
                'reason' => 'Ditolak melalui action Filament sintetis',
            ]);

        $fresh = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());
        $notes = json_decode((string) $fresh->notes, true);
        $this->assertSame(DecreeProposal::STATUS_REJECTED, $fresh->status);
        $this->assertSame('Ditolak melalui action Filament sintetis', $notes['decision_reason']);
        $this->assertSame('Ditolak melalui action Filament sintetis', $notes['rejection_reason']);
    }

    public function test_rejected_candidate_without_employee_can_be_submitted_then_approved_normally(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_REJECTED, $adminInduk);
        $this->putRequiredDocuments($proposal);

        app(DecreeProposalStatusService::class)->update(
            $proposal,
            $adminInduk,
            DecreeProposal::STATUS_SUBMITTED,
            'Siap diproses ulang',
        );

        $before = $this->masterCounts();
        $result = app(DecreeProposalApprovalService::class)->approve($proposal, $adminInduk);
        $fresh = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());

        $this->assertFalse($result['reused_existing']);
        $this->assertSame(DecreeProposal::STATUS_COMPLETED, $fresh->status);
        $this->assertNotNull($fresh->employee_id);
        $this->assertSame($before['users'] + 1, User::on('sidikma_induk')->count());
        $this->assertSame($before['employees'] + 1, Employee::on('sidikma_induk')->count());
        $this->assertSame($before['memberships'] + 1, Membership::on('sidikma_induk')->count());
        $this->assertSame($before['assignments'] + 1, EmployeeAssignment::on('sidikma_induk')->count());
    }

    public function test_valid_existing_employee_is_reused_without_duplicates_and_survives_status_changes(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        [$proposal, $employee] = $this->makeProposalWithEmployee(
            $foundation,
            $school,
            $position,
            DecreeProposal::STATUS_SUBMITTED,
            $adminInduk,
        );
        $this->putRequiredDocuments($proposal);
        $before = $this->masterCounts();

        $result = app(DecreeProposalApprovalService::class)->approve($proposal, $adminInduk);
        $afterApproval = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());
        $this->assertTrue($result['reused_existing']);
        $this->assertSame($employee->getKey(), $afterApproval->employee_id);
        $this->assertSame(DecreeProposal::STATUS_COMPLETED, $afterApproval->status);
        $this->assertSame($before, $this->masterCounts());

        app(DecreeProposalStatusService::class)->update(
            $afterApproval,
            $adminInduk,
            DecreeProposal::STATUS_REJECTED,
            'Perlu peninjauan ulang',
        );
        app(DecreeProposalStatusService::class)->update(
            $afterApproval,
            $adminInduk,
            DecreeProposal::STATUS_SUBMITTED,
            'Diajukan kembali',
        );

        $freshEmployee = Employee::on('sidikma_induk')->withTrashed()->findOrFail($employee->getKey());
        $freshProposal = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());
        $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $freshProposal->status);
        $this->assertSame($employee->getKey(), $freshProposal->employee_id);
        $this->assertTrue((bool) $freshEmployee->is_active);
        $this->assertSame($before, $this->masterCounts());
    }

    /** @return array{0: Foundation, 1: School, 2: EmployeePosition, 3: User, 4: User, 5: User} */
    private function makeContext(): array
    {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $foundation = Foundation::query()->create([
            'name' => "Status Foundation {$token}",
            'code' => "STS-{$token}",
            'is_active' => true,
        ]);
        $school = School::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Status School {$token}",
            'npsn' => (string) random_int(10000000, 99999999),
            'school_level' => 'SMA',
            'is_active' => true,
        ]);
        $position = EmployeePosition::on('sidikma_induk')->create([
            'code' => "STS-POS-{$token}",
            'name' => "Status Position {$token}",
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'is_active' => true,
        ]);
        $adminInduk = User::factory()->create(['email' => "status-induk-{$token}@example.test"]);
        $adminInduk->assignRole(User::ROLE_ADMIN_INDUK);
        $adminSekolah = User::factory()->create(['email' => "status-sekolah-{$token}@example.test"]);
        $adminSekolah->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $adminSekolah->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);
        $roleThree = User::factory()->create(['email' => "status-role3-{$token}@example.test"]);
        $roleThree->assignRole(User::ROLE_GURU_PEGAWAI);

        return [$foundation, $school, $position, $adminInduk, $adminSekolah, $roleThree];
    }

    private function makeProposal(
        Foundation $foundation,
        School $school,
        EmployeePosition $position,
        string $status,
        User $submittedBy,
    ): DecreeProposal {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));

        return DecreeProposal::on('sidikma_induk')->create([
            'employee_id' => null,
            'candidate_name' => "Status Candidate {$token}",
            'candidate_email' => "status-candidate-{$token}@example.test",
            'candidate_phone' => '081234567890',
            'candidate_school' => $school->name,
            'legacy_snapshot' => $this->snapshot($foundation, $school, $position, $token),
            'status' => $status,
            'notes' => json_encode([
                'initial' => 'Catatan awal sintetis',
                'decision_reason' => 'Alasan sebelumnya',
                'rejection_reason' => 'Alasan lama',
                'decision_status' => $status,
                'decision_updated_by' => 111,
            ], JSON_THROW_ON_ERROR),
            'submitted_by' => $submittedBy->getKey(),
        ]);
    }

    /** @return array{0: DecreeProposal, 1: Employee} */
    private function makeProposalWithEmployee(
        Foundation $foundation,
        School $school,
        EmployeePosition $position,
        string $status,
        User $submittedBy,
    ): array {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $candidateUser = User::factory()->create(['email' => "legacy-candidate-{$token}@example.test"]);
        $employee = Employee::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'user_id' => $candidateUser->getKey(),
            'employee_code' => "LEGACY-{$token}",
            'name' => "Legacy Candidate {$token}",
            'employee_type' => Employee::TYPE_GURU,
            'employment_status' => 'GTY',
            'last_education' => 'S1',
            'program_study' => 'Pendidikan',
            'birth_place' => 'Yogyakarta',
            'birth_date' => '1990-01-01',
            'phone' => '081234567890',
            'email' => $candidateUser->email,
            'is_active' => true,
        ]);
        Membership::on('sidikma_induk')->create([
            'user_id' => $candidateUser->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);
        EmployeeAssignment::on('sidikma_induk')->create([
            'employee_id' => $employee->getKey(),
            'employee_position_id' => $position->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'start_date' => '2020-01-01',
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => true,
        ]);
        $proposal = DecreeProposal::on('sidikma_induk')->create([
            'employee_id' => $employee->getKey(),
            'candidate_name' => $employee->name,
            'candidate_email' => $employee->email,
            'candidate_phone' => $employee->phone,
            'candidate_school' => $school->name,
            'legacy_snapshot' => $this->snapshot($foundation, $school, $position, $token, $employee),
            'status' => $status,
            'notes' => json_encode(['initial' => 'Catatan legacy sintetis'], JSON_THROW_ON_ERROR),
            'submitted_by' => $submittedBy->getKey(),
        ]);

        return [$proposal, $employee];
    }

    /** @return array<string, mixed> */
    private function snapshot(
        Foundation $foundation,
        School $school,
        EmployeePosition $position,
        string $token,
        ?Employee $employee = null,
    ): array {
        return [
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'school_name' => $school->name,
            'employee_code' => $employee?->employee_code ?: "STATUS-{$token}",
            'name' => $employee?->name ?: "Status Candidate {$token}",
            'email' => $employee?->email ?: "status-candidate-{$token}@example.test",
            'phone' => $employee?->phone ?: '081234567890',
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
    }

    private function putRequiredDocuments(DecreeProposal $proposal): void
    {
        foreach ([
            $proposal->photo_path ?: "status/{$proposal->getKey()}-photo.jpg",
            $proposal->diploma_path ?: "status/{$proposal->getKey()}-diploma.pdf",
            $proposal->application_letter_path ?: "status/{$proposal->getKey()}-application.pdf",
            $proposal->service_statement_path ?: "status/{$proposal->getKey()}-statement.pdf",
            $proposal->task_assignment_certificate_path ?: "status/{$proposal->getKey()}-task.pdf",
        ] as $path) {
            Storage::disk('decree-proposals')->put($path, 'synthetic testing document');
        }
        $proposal->forceFill([
            'photo_path' => "status/{$proposal->getKey()}-photo.jpg",
            'diploma_path' => "status/{$proposal->getKey()}-diploma.pdf",
            'application_letter_path' => "status/{$proposal->getKey()}-application.pdf",
            'service_statement_path' => "status/{$proposal->getKey()}-statement.pdf",
            'task_assignment_certificate_path' => "status/{$proposal->getKey()}-task.pdf",
        ])->save();
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
