<?php

namespace Tests\Feature;

use App\Models\DecreeProposal;
use App\Models\User;
use App\Services\DecreeProposalApprovalService;
use App\Services\DecreeProposalStatusService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class DecreeProposalLegacyCompatibilityTest extends TestCase
{
    private function adminInduk(): User
    {
        $admin = Mockery::mock(User::class);
        $admin->shouldReceive('isAdminInduk')->andReturn(true);
        $admin->shouldReceive('can')->andReturn(true);
        $admin->shouldReceive('getKey')->andReturn(900001);

        return $admin;
    }

    private function proposal(string $status, ?int $legacyId = null): DecreeProposal
    {
        return DecreeProposal::on('sqlite')->create([
            'legacy_proposal_id' => $legacyId,
            'candidate_name' => 'Legacy Sintetis '.$status,
            'candidate_email' => 'legacy-'.str_replace('_', '-', $status).'-'.random_int(1000, 9999).'@example.test',
            'candidate_school' => 'Sekolah Sintetis',
            'legacy_snapshot' => ['employee_code' => 'LEG-'.random_int(1000, 9999)],
            'status' => $status,
            'notes' => json_encode(['initial' => 'Catatan awal sintetis'], JSON_THROW_ON_ERROR),
        ]);
    }

    public function test_status_labels_and_actual_status_groups_are_compatible(): void
    {
        $this->beginDatabaseTransaction();

        try {
            $beforeTotal = DecreeProposal::on('sqlite')->count();
            $beforeApproved = DecreeProposal::on('sqlite')
                ->whereIn('status', [DecreeProposal::STATUS_APPROVED, DecreeProposal::STATUS_COMPLETED])
                ->count();
            $beforeRevision = DecreeProposal::on('sqlite')->where('status', DecreeProposal::STATUS_REVISION)->count();
            foreach ([
                DecreeProposal::STATUS_SUBMITTED,
                DecreeProposal::STATUS_APPROVED,
                DecreeProposal::STATUS_COMPLETED,
                DecreeProposal::STATUS_REVISION,
                DecreeProposal::STATUS_REJECTED,
            ] as $status) {
                $this->proposal($status);
            }
            $deleted = $this->proposal(DecreeProposal::STATUS_APPROVED);
            $deleted->delete();

            $this->assertSame('Menunggu Verifikasi', DecreeProposal::statusOptions()[DecreeProposal::STATUS_SUBMITTED]);
            $this->assertSame('Disetujui', DecreeProposal::statusOptions()[DecreeProposal::STATUS_APPROVED]);
            $this->assertSame('Disetujui', DecreeProposal::statusOptions()[DecreeProposal::STATUS_COMPLETED]);
            $this->assertSame('Perlu Revisi', DecreeProposal::statusOptions()[DecreeProposal::STATUS_REVISION]);
            $this->assertSame('Ditolak', DecreeProposal::statusOptions()[DecreeProposal::STATUS_REJECTED]);

            $approved = DecreeProposal::on('sqlite')
                ->whereIn('status', [DecreeProposal::STATUS_APPROVED, DecreeProposal::STATUS_COMPLETED])
                ->count();
            $this->assertSame($beforeApproved + 2, $approved);
            $this->assertSame($beforeRevision + 1, DecreeProposal::on('sqlite')->where('status', DecreeProposal::STATUS_REVISION)->count());
            $this->assertSame($beforeTotal + 5, DecreeProposal::on('sqlite')->count());
        } finally {
            $this->rollbackDatabaseTransaction();
        }
    }

    public function test_admin_status_service_preserves_legacy_notes_and_supports_repeated_decisions(): void
    {
        $this->beginDatabaseTransaction();

        try {
            $proposal = $this->proposal(DecreeProposal::STATUS_APPROVED, 88001);
            $admin = $this->adminInduk();
            $service = app(DecreeProposalStatusService::class);

            foreach ([
                DecreeProposal::STATUS_REVISION,
                DecreeProposal::STATUS_SUBMITTED,
                DecreeProposal::STATUS_REJECTED,
                DecreeProposal::STATUS_APPROVED,
            ] as $status) {
                $service->update($proposal, $admin, $status, 'Tindak lanjut '.$status);
                $proposal->refresh();
                $notes = json_decode((string) $proposal->notes, true);
                $this->assertSame($status, $proposal->status);
                $this->assertSame('Catatan awal sintetis', $notes['initial']);
                $this->assertSame('Tindak lanjut '.$status, $notes['decision_reason']);
                $this->assertSame($status, $notes['decision_status']);
                $this->assertSame(88001, $proposal->legacy_proposal_id);
            }
        } finally {
            $this->rollbackDatabaseTransaction();
        }
    }

    public function test_historical_legacy_proposal_without_employee_is_not_provisioned(): void
    {
        $this->beginDatabaseTransaction();

        try {
            $proposal = $this->proposal(DecreeProposal::STATUS_APPROVED, 88002);
            $beforeUsers = User::on('sqlite')->count();
            $beforeEmployees = \App\Models\Employee::on('sqlite')->count();

            $this->expectException(ValidationException::class);
            try {
                app(DecreeProposalApprovalService::class)->approve($proposal, $this->adminInduk());
            } finally {
                $this->assertSame($beforeUsers, User::on('sqlite')->count());
                $this->assertSame($beforeEmployees, \App\Models\Employee::on('sqlite')->count());
            }
        } finally {
            $this->rollbackDatabaseTransaction();
        }
    }

    public function test_nonlegacy_submitted_proposal_is_not_marked_historical(): void
    {
        $proposal = new DecreeProposal(['status' => DecreeProposal::STATUS_SUBMITTED]);

        $this->assertFalse($proposal->isHistoricalLegacy());
        $proposal->legacy_proposal_id = 88003;
        $this->assertTrue($proposal->isHistoricalLegacy());
    }

    private function beginDatabaseTransaction(): void
    {
        \DB::connection('sqlite')->beginTransaction();
    }

    private function rollbackDatabaseTransaction(): void
    {
        $connection = \DB::connection('sqlite');
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
    }
}
