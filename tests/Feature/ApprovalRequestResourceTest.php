<?php

namespace Tests\Feature;

use App\Filament\RelationManagers\ApprovalRequestsRelationManager;
use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\ApprovalRequests\ApprovalRequestResource;
use App\Filament\Resources\ApprovalRequests\Pages\ListApprovalRequests;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Foundations\FoundationResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Models\ApprovalRequest;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use App\Services\ApprovalWorkflow;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($this->admin);
    }

    public function test_approval_history_manager_is_available_for_supported_resource_pages(): void
    {
        $this->assertContains(ApprovalRequestsRelationManager::class, EmployeeResource::getRelations());
        $this->assertContains(ApprovalRequestsRelationManager::class, FoundationResource::getRelations());
        $this->assertContains(ApprovalRequestsRelationManager::class, SchoolResource::getRelations());
    }

    public function test_admin_can_open_approval_list_and_detail_pages(): void
    {
        $request = app(ApprovalWorkflow::class)->createAndSubmit(
            Employee::factory()->create(),
            $this->admin,
            'Mohon diperiksa.',
        );

        $this->get(ApprovalRequestResource::getUrl('index', panel: 'admin'))
            ->assertOk()
            ->assertSee('Daftar Approval')
            ->assertSee('Diajukan');

        $this->get(ApprovalRequestResource::getUrl('view', ['record' => $request], panel: 'admin'))
            ->assertOk()
            ->assertSee('Mohon diperiksa.')
            ->assertSee($this->admin->name);
    }

    public function test_admin_can_submit_employee_approval_from_relation_manager(): void
    {
        $employee = Employee::factory()->create();

        Livewire::test(ApprovalRequestsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ])
            ->callTableAction('submitApproval', data: [
                'notes' => 'Pengajuan data pegawai.',
            ])
            ->assertHasNoFormErrors();

        $request = $employee->approvalRequests()->sole();

        $this->assertSame(ApprovalRequest::STATUS_SUBMITTED, $request->status);
        $this->assertSame('Pengajuan data pegawai.', $request->submission_notes);
        $this->assertTrue($request->submittedBy->is($this->admin));
    }

    public function test_admin_can_submit_document_approval_from_document_manager(): void
    {
        $employee = Employee::factory()->create();
        $document = Document::factory()->create([
            'owner_type' => Employee::class,
            'owner_id' => $employee->getKey(),
            'uploaded_by' => $this->admin->getKey(),
        ]);

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ])
            ->callTableAction('submitApproval', $document, data: [
                'notes' => 'Mohon verifikasi dokumen.',
            ])
            ->assertHasNoFormErrors();

        $request = $document->approvalRequests()->sole();

        $this->assertSame(ApprovalRequest::STATUS_SUBMITTED, $request->status);
        $this->assertSame('Mohon verifikasi dokumen.', $request->submission_notes);
    }

    public function test_open_approval_request_cannot_be_submitted_twice(): void
    {
        $employee = Employee::factory()->create();
        $workflow = app(ApprovalWorkflow::class);

        $workflow->createAndSubmit($employee, $this->admin);

        try {
            $workflow->createAndSubmit($employee, $this->admin);

            $this->fail('Data dengan approval aktif seharusnya tidak dapat diajukan kembali.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Data ini masih memiliki proses approval yang aktif.',
                $exception->errors()['approvable'][0],
            );
        }

        $this->assertSame(1, $employee->approvalRequests()->count());
    }

    public function test_admin_can_verify_and_approve_from_central_approval_table(): void
    {
        $request = app(ApprovalWorkflow::class)->createAndSubmit(
            Employee::factory()->create(),
            $this->admin,
        );

        Livewire::test(ListApprovalRequests::class)
            ->callTableAction('verify', $request, data: [
                'notes' => 'Data lengkap.',
            ])
            ->assertHasNoFormErrors();

        $request->refresh();
        $this->assertSame(ApprovalRequest::STATUS_VERIFIED, $request->status);
        $this->assertSame('Data lengkap.', $request->verification_notes);

        Livewire::test(ListApprovalRequests::class)
            ->callTableAction('approve', $request, data: [
                'notes' => 'Disetujui pimpinan.',
            ])
            ->assertHasNoFormErrors();

        $request->refresh();
        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('Disetujui pimpinan.', $request->decision_notes);
        $this->assertTrue($request->decidedBy->is($this->admin));
    }

    public function test_admin_can_reject_from_central_table_and_notes_are_required(): void
    {
        $request = app(ApprovalWorkflow::class)->createAndSubmit(
            Employee::factory()->create(),
            $this->admin,
        );

        Livewire::test(ListApprovalRequests::class)
            ->callTableAction('reject', $request, data: ['notes' => null])
            ->assertHasFormErrors(['notes' => 'required']);

        $this->assertSame(ApprovalRequest::STATUS_SUBMITTED, $request->fresh()->status);

        Livewire::test(ListApprovalRequests::class)
            ->callTableAction('reject', $request, data: [
                'notes' => 'Lampiran belum sesuai.',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(ApprovalRequest::STATUS_REJECTED, $request->fresh()->status);
        $this->assertSame('Lampiran belum sesuai.', $request->fresh()->decision_notes);
    }

    public function test_approval_table_filters_by_status_and_data_type(): void
    {
        $employeeRequest = app(ApprovalWorkflow::class)->createAndSubmit(
            Employee::factory()->create(),
            $this->admin,
        );
        $documentRequest = app(ApprovalWorkflow::class)->createAndSubmit(
            Document::factory()->create(['uploaded_by' => $this->admin->getKey()]),
            $this->admin,
        );
        $documentRequest->verify($this->admin)->approve($this->admin);

        Livewire::test(ListApprovalRequests::class)
            ->filterTable('status', ApprovalRequest::STATUS_SUBMITTED)
            ->assertCanSeeTableRecords([$employeeRequest])
            ->assertCanNotSeeTableRecords([$documentRequest]);

        Livewire::test(ListApprovalRequests::class)
            ->filterTable('approvable_type', Document::class)
            ->assertCanSeeTableRecords([$documentRequest])
            ->assertCanNotSeeTableRecords([$employeeRequest]);
    }

    public function test_non_admin_induk_cannot_access_or_process_approval_resource(): void
    {
        $request = app(ApprovalWorkflow::class)->createAndSubmit(
            Employee::factory()->create(),
            $this->admin,
        );
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        $this->actingAs($schoolAdmin);

        $this->get(ApprovalRequestResource::getUrl('index', panel: 'admin'))->assertForbidden();
        $this->get(ApprovalRequestResource::getUrl('view', ['record' => $request], panel: 'admin'))
            ->assertForbidden();
    }
}
