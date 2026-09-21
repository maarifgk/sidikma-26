<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApprovalRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_requests_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('approval_requests', [
            'id',
            'approvable_type',
            'approvable_id',
            'status',
            'submitted_by',
            'verified_by',
            'decided_by',
            'submitted_at',
            'verified_at',
            'decided_at',
            'submission_notes',
            'verification_notes',
            'decision_notes',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));
    }

    public function test_approval_requests_can_belong_to_supported_models(): void
    {
        $document = Document::factory()->create();
        $employee = Employee::factory()->create();
        $foundation = Foundation::factory()->create();
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'Madrasah Approval',
            'npsn' => '87654321',
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        $documentRequest = $document->approvalRequests()->create();
        $employeeRequest = $employee->approvalRequests()->create();
        $foundationRequest = $foundation->approvalRequests()->create();
        $schoolRequest = $school->approvalRequests()->create();

        $this->assertTrue($documentRequest->approvable->is($document));
        $this->assertTrue($employeeRequest->approvable->is($employee));
        $this->assertTrue($foundationRequest->approvable->is($foundation));
        $this->assertTrue($schoolRequest->approvable->is($school));
        $this->assertTrue($document->approvalRequests->contains($documentRequest));
        $this->assertTrue($employee->approvalRequests->contains($employeeRequest));
        $this->assertTrue($foundation->approvalRequests->contains($foundationRequest));
        $this->assertTrue($school->approvalRequests->contains($schoolRequest));
    }

    public function test_request_follows_complete_approval_workflow_and_records_actors(): void
    {
        $submitter = User::factory()->create();
        $verifier = User::factory()->create();
        $approver = User::factory()->create();
        $request = ApprovalRequest::factory()->create();

        $this->assertSame(ApprovalRequest::STATUS_DRAFT, $request->status);
        $this->assertTrue($request->canTransitionTo(ApprovalRequest::STATUS_SUBMITTED));

        $request->submit($submitter, 'Data sudah dilengkapi.');

        $this->assertSame(ApprovalRequest::STATUS_SUBMITTED, $request->status);
        $this->assertSame('Data sudah dilengkapi.', $request->submission_notes);
        $this->assertNotNull($request->submitted_at);
        $this->assertTrue($request->submittedBy->is($submitter));
        $this->assertTrue($submitter->submittedApprovalRequests->contains($request));

        $request->verify($verifier, 'Data sudah diverifikasi.');

        $this->assertSame(ApprovalRequest::STATUS_VERIFIED, $request->status);
        $this->assertSame('Data sudah diverifikasi.', $request->verification_notes);
        $this->assertNotNull($request->verified_at);
        $this->assertTrue($request->verifiedBy->is($verifier));
        $this->assertTrue($verifier->verifiedApprovalRequests->contains($request));

        $request->approve($approver, 'Disetujui.');

        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('Disetujui.', $request->decision_notes);
        $this->assertNotNull($request->decided_at);
        $this->assertTrue($request->decidedBy->is($approver));
        $this->assertTrue($approver->decidedApprovalRequests->contains($request));
        $this->assertFalse($request->canTransitionTo(ApprovalRequest::STATUS_REJECTED));
    }

    public function test_status_cannot_be_skipped_or_changed_directly(): void
    {
        $actor = User::factory()->create();
        $request = ApprovalRequest::factory()->create();

        try {
            $request->approve($actor);

            $this->fail('Status draft seharusnya tidak dapat langsung disetujui.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'draft tidak dapat diubah menjadi approved',
                $exception->errors()['status'][0],
            );
        }

        $this->assertSame(ApprovalRequest::STATUS_DRAFT, $request->fresh()->status);

        try {
            $request->status = ApprovalRequest::STATUS_SUBMITTED;
            $request->save();

            $this->fail('Status seharusnya tidak dapat diubah langsung.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Perubahan status wajib melalui alur approval.',
                $exception->errors()['status'][0],
            );
        }

        $this->assertSame(ApprovalRequest::STATUS_DRAFT, $request->fresh()->status);
    }

    public function test_rejection_requires_notes_and_is_terminal(): void
    {
        $submitter = User::factory()->create();
        $reviewer = User::factory()->create();
        $request = ApprovalRequest::factory()->create()->submit($submitter);

        try {
            $request->reject($reviewer, '');

            $this->fail('Penolakan tanpa alasan seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Alasan penolakan wajib diisi.',
                $exception->errors()['decision_notes'][0],
            );
        }

        $request->reject($reviewer, 'Nomor surat belum sesuai.');

        $this->assertSame(ApprovalRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('Nomor surat belum sesuai.', $request->decision_notes);
        $this->assertTrue($request->decidedBy->is($reviewer));

        $this->expectException(ValidationException::class);

        $request->verify($reviewer);
    }

    public function test_new_request_must_start_as_draft(): void
    {
        $this->expectException(ValidationException::class);

        ApprovalRequest::factory()->create([
            'status' => ApprovalRequest::STATUS_APPROVED,
        ]);
    }

    public function test_request_uses_soft_deletes(): void
    {
        $request = ApprovalRequest::factory()->create();

        $request->delete();

        $this->assertNull(ApprovalRequest::query()->find($request->getKey()));
        $this->assertNotNull(ApprovalRequest::withTrashed()->find($request->getKey()));
        $this->assertSoftDeleted('approval_requests', ['id' => $request->getKey()]);
    }

    public function test_approval_policy_requires_admin_induk_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $request = ApprovalRequest::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $request));
        $this->assertTrue(Gate::forUser($admin)->allows('create', ApprovalRequest::class));
        $this->assertTrue(Gate::forUser($admin)->allows('submit', $request));
        $this->assertTrue(Gate::forUser($admin)->allows('verify', $request));
        $this->assertTrue(Gate::forUser($admin)->allows('approve', $request));
        $this->assertTrue(Gate::forUser($admin)->allows('reject', $request));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $request));
        $this->assertFalse(Gate::forUser($admin)->allows('forceDelete', $request));
        $this->assertFalse(Gate::forUser($schoolAdmin)->allows('view', $request));
        $this->assertFalse(Gate::forUser($schoolAdmin)->allows('submit', $request));
        $this->assertFalse(Gate::forUser($schoolAdmin)->allows('approve', $request));

        $request->submit($admin)->verify($admin)->approve($admin);

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $request));
    }
}
