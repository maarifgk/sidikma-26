<?php

namespace Tests\Feature;

use App\Filament\Resources\DecreeProposals\Pages\ListDecreeProposals;
use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use App\Services\DecreeProposalEditService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DecreeProposalSaveTest extends TestCase
{
    public function test_both_admin_roles_can_save_all_statuses_without_changing_protected_data(): void
    {
        [$foundation, $school, $position, $adminInduk, $adminSekolah] = $this->makeContext();

        foreach ([
            'Admin Induk' => $adminInduk,
            'Admin Sekolah' => $adminSekolah,
        ] as $role => $actor) {
            foreach (DecreeProposal::statusOptions() as $status => $_label) {
                [$proposal, $candidateUser, $employee] = $this->makeProposal(
                    $foundation,
                    $school,
                    $position,
                    $status,
                    $actor,
                );

                $before = $this->protectedSnapshot($proposal, $candidateUser, $employee);
                $data = $this->editData($proposal, $position, $employee, $role);

                app(DecreeProposalEditService::class)->save($proposal, $actor, $data);

                $fresh = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());
                $after = $this->protectedSnapshot($fresh, $candidateUser->fresh(), $employee->fresh());

                $this->assertSame($before['status'], $after['status'], "$role/$status mengubah status.");
                $this->assertSame($before['employee_id'], $after['employee_id'], "$role/$status mengubah employee_id.");
                $this->assertSame($before['decision'], $after['decision'], "$role/$status mengubah keterangan keputusan.");
                $this->assertSame($before['documents'], $after['documents'], "$role/$status mengubah dokumen existing.");
                $this->assertSame($before['master_counts'], $after['master_counts'], "$role/$status mengubah jumlah data master.");
                $this->assertSame($before['employee'], $after['employee'], "$role/$status mengubah data Employee.");
                $this->assertSame($before['user'], $after['user'], "$role/$status mengubah data User.");
                $this->assertSame($before['assignment_count'], $after['assignment_count'], "$role/$status mengubah EmployeeAssignment.");
                $this->assertSame("Edited {$role} {$status}", $fresh->candidate_name);
                $this->assertSame($employee->email, $fresh->candidate_email);
                $this->assertSame("Catatan edit {$role} {$status}", $fresh->initialNotes());
            }
        }
    }

    public function test_filament_edit_action_delegates_to_save_service_for_school_admin(): void
    {
        [$foundation, $school, $position, $_adminInduk, $adminSekolah] = $this->makeContext();
        [$proposal, $candidateUser, $employee] = $this->makeProposal(
            $foundation,
            $school,
            $position,
            DecreeProposal::STATUS_COMPLETED,
            $adminSekolah,
        );
        $beforeStatus = $proposal->status;
        $beforeNotes = json_decode((string) $proposal->notes, true);
        $beforeEmployeeId = $proposal->employee_id;
        $data = $this->editData($proposal, $position, $employee, 'Filament');

        filament()->setCurrentPanel(filament()->getPanel('app'));

        Livewire::actingAs($adminSekolah)
            ->test(ListDecreeProposals::class)
            ->assertTableActionVisible('edit', $proposal)
            ->callTableAction('edit', $proposal, data: $data);

        $fresh = DecreeProposal::on('sidikma_induk')->findOrFail($proposal->getKey());
        $freshNotes = json_decode((string) $fresh->notes, true);

        $this->assertSame('Edited Filament completed', $fresh->candidate_name);
        $this->assertSame($beforeStatus, $fresh->status);
        $this->assertSame($beforeEmployeeId, $fresh->employee_id);
        $this->assertSame($beforeNotes['decision_reason'], $freshNotes['decision_reason']);
        $this->assertSame($beforeNotes['rejection_reason'], $freshNotes['rejection_reason']);
        $this->assertSame($candidateUser->id, $employee->user_id);
    }

    /** @return array{0: Foundation, 1: School, 2: EmployeePosition, 3: User, 4: User} */
    private function makeContext(): array
    {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $foundation = Foundation::query()->create([
            'name' => "Synthetic Foundation {$token}",
            'code' => "SYN-{$token}",
            'is_active' => true,
        ]);
        $school = School::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Synthetic School {$token}",
            'npsn' => substr((string) random_int(10000000, 99999999), 0, 8),
            'school_level' => 'SMA',
            'is_active' => true,
        ]);
        $position = EmployeePosition::on('sidikma_induk')->create([
            'code' => "SYN-POS-{$token}",
            'name' => "Synthetic Position {$token}",
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'is_active' => true,
        ]);
        $adminInduk = User::factory()->create(['email' => "admin-induk-{$token}@example.test"]);
        $adminInduk->assignRole(User::ROLE_ADMIN_INDUK);
        $adminSekolah = User::factory()->create(['email' => "admin-sekolah-{$token}@example.test"]);
        $adminSekolah->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $adminSekolah->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);

        return [$foundation, $school, $position, $adminInduk, $adminSekolah];
    }

    /** @return array{0: DecreeProposal, 1: User, 2: Employee} */
    private function makeProposal(
        Foundation $foundation,
        School $school,
        EmployeePosition $position,
        string $status,
        User $actor,
    ): array {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $candidateUser = User::factory()->create(['email' => "candidate-{$token}@example.test"]);
        $employee = Employee::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'user_id' => $candidateUser->getKey(),
            'employee_code' => "SYN-EMP-{$token}",
            'name' => "Candidate {$token}",
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
        $decision = [
            'decision_reason' => 'Keputusan sintetis tetap',
            'rejection_reason' => 'Alasan sintetis tetap',
            'decision_status' => $status,
            'decision_updated_by' => 987654,
        ];
        $proposal = DecreeProposal::on('sidikma_induk')->create([
            'employee_id' => $employee->getKey(),
            'candidate_name' => "Candidate {$token}",
            'candidate_email' => $candidateUser->email,
            'candidate_phone' => '081234567890',
            'candidate_school' => $school->name,
            'legacy_snapshot' => [
                'foundation_id' => $foundation->getKey(),
                'school_id' => $school->getKey(),
                'school_name' => $school->name,
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'email' => $candidateUser->email,
                'phone' => $employee->phone,
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
            ],
            'partner_admin_number' => "PARTNER-{$token}",
            'status' => $status,
            'notes' => json_encode(['initial' => 'Catatan sintetis awal'] + $decision, JSON_THROW_ON_ERROR),
            'photo_path' => "synthetic/{$token}.jpg",
            'diploma_path' => "synthetic/{$token}.pdf",
            'application_letter_path' => "synthetic/{$token}-application.pdf",
            'service_statement_path' => "synthetic/{$token}-statement.pdf",
            'task_assignment_certificate_path' => "synthetic/{$token}-task.pdf",
            'submitted_by' => $actor->getKey(),
        ]);

        return [$proposal, $candidateUser, $employee];
    }

    /** @return array<string, mixed> */
    private function editData(DecreeProposal $proposal, EmployeePosition $position, Employee $employee, string $role): array
    {
        $status = $proposal->status;

        return [
            'edit_version' => $proposal->getRawOriginal('updated_at'),
            'name' => "Edited {$role} {$status}",
            'employee_code' => $employee->employee_code,
            // Keep the linked employee email to prove self-conflict validation excludes the current record.
            'email' => $employee->email,
            'phone' => '081298765432',
            'partner_admin_number' => $proposal->partner_admin_number,
            'employment_status' => 'GTY',
            'nip' => null,
            'rank' => null,
            'grade' => null,
            'birth_place' => 'Bantul',
            'birth_date' => '1991-02-02',
            'position_id' => $position->getKey(),
            'assignment_start_date' => '2021-02-02',
            'last_education' => 'S2',
            'nuptk' => null,
            'program_study' => 'Manajemen Pendidikan',
            'notes' => "Catatan edit {$role} {$status}",
        ];
    }

    /** @return array<string, mixed> */
    private function protectedSnapshot(DecreeProposal $proposal, User $candidateUser, Employee $employee): array
    {
        $notes = json_decode((string) $proposal->notes, true);

        return [
            'status' => $proposal->status,
            'employee_id' => $proposal->employee_id,
            'decision' => [
                $notes['decision_reason'] ?? null,
                $notes['rejection_reason'] ?? null,
                $notes['decision_status'] ?? null,
                $notes['decision_updated_by'] ?? null,
            ],
            'documents' => [
                $proposal->photo_path,
                $proposal->diploma_path,
                $proposal->application_letter_path,
                $proposal->service_statement_path,
                $proposal->task_assignment_certificate_path,
            ],
            'master_counts' => [
                User::on('sidikma_induk')->count(),
                Employee::on('sidikma_induk')->withTrashed()->count(),
                Membership::on('sidikma_induk')->count(),
            ],
            'employee' => $employee->only(['user_id', 'employee_code', 'name', 'email', 'phone', 'school_id']),
            'user' => $candidateUser->only(['name', 'email', 'is_active']),
            'assignment_count' => DB::connection('sidikma_induk')
                ->table('employee_assignments')
                ->where('employee_id', $employee->getKey())
                ->count(),
        ];
    }
}
