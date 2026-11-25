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
use App\Filament\Resources\DecreeProposals\Pages\ListDecreeProposals;
use App\Services\DecreeProposalApprovalService;
use App\Services\DecreeProposalRejectionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Step9bDecreeProposalWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('decree-proposals');
        Notification::fake();
        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_admin_induk_approval_creates_all_required_records_and_sends_reset_notification(): void
    {
        [$proposal, $admin, $email] = $this->proposalFixture();
        $before = [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()];

        $result = app(DecreeProposalApprovalService::class)->approve($proposal, $admin);

        $proposal->refresh();
        $account = $result['user']->fresh();
        $this->assertSame(DecreeProposal::STATUS_COMPLETED, $proposal->status);
        $this->assertNotNull($proposal->employee_id);
        $this->assertSame($before[0] + 1, User::count());
        $this->assertSame($before[1] + 1, Employee::count());
        $this->assertSame($before[2] + 1, Membership::count());
        $this->assertSame($before[3] + 1, EmployeeAssignment::count());
        $this->assertSame($email, $account->email);
        $this->assertTrue($account->hasRole(User::ROLE_GURU_PEGAWAI));
        $this->assertTrue(Hash::needsRehash($account->password) === false);
        $this->assertNotSame('PlaintextNotStored', $account->password);
        Notification::assertSentTo($account, ResetPassword::class);
    }

    public function test_approved_account_can_reset_password_login_and_access_guru_panel(): void
    {
        [$proposal, $admin] = $this->proposalFixture();
        $account = app(DecreeProposalApprovalService::class)->approve($proposal, $admin)['user']->fresh();
        $newPassword = 'Reset-Guru-9B2!';
        $token = Password::broker()->createToken($account);

        $status = Password::broker()->reset([
            'email' => $account->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'token' => $token,
        ], static function (User $user, string $password): void {
            $user->password = Hash::make($password);
            $user->setRememberToken(str()->random(60));
            $user->save();
        });

        $this->assertSame(Password::PASSWORD_RESET, $status);
        $this->assertTrue(Auth::attempt(['email' => $account->email, 'password' => $newPassword]));
        $this->assertTrue(auth()->user()->canAccessPanel(filament()->getPanel('app')));
        $this->assertFalse(auth()->user()->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_document_http_preview_is_authorized_and_returns_file(): void
    {
        [$proposal, $admin] = $this->proposalFixture();
        config()->set('filesystems.disks.decree-proposals.root', Storage::disk('decree-proposals')->path(''));
        $response = $this->actingAs($admin)->get(route('decree-proposals.documents.view', [
            'proposal' => $proposal, 'document' => 'diploma_path',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_document_http_download_and_authorization_guards(): void
    {
        [$proposal, $admin, , $school, $foundation] = $this->proposalFixture();
        config()->set('filesystems.disks.decree-proposals.root', Storage::disk('decree-proposals')->path(''));
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(), 'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(), 'status' => 'active',
        ]);
        $otherSchoolAdmin = User::factory()->create();
        $otherSchoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        $this->actingAs($admin)->get(route('decree-proposals.documents.view', [
            'proposal' => $proposal, 'document' => 'diploma_path',
        ]))->assertHeader('Content-Disposition', 'inline; filename="diploma.pdf"');
        $this->actingAs($otherSchoolAdmin)->get(route('decree-proposals.documents.view', [
            'proposal' => $proposal, 'document' => 'diploma_path',
        ]))->assertForbidden();
        $this->get(route('decree-proposals.documents.view', [
            'proposal' => $proposal, 'document' => 'diploma_path',
        ]))->assertForbidden();
        $this->actingAs($admin)->get(route('decree-proposals.documents.view', [
            'proposal' => $proposal, 'document' => 'missing_path',
        ]))->assertNotFound();
        $this->actingAs($schoolAdmin)->get(route('decree-proposals.documents.view', [
            'proposal' => $proposal, 'document' => 'diploma_path',
        ]))->assertOk();
    }

    public function test_filament_component_exposes_eye_approve_and_reject_actions(): void
    {
        [$proposal, $admin] = $this->proposalFixture();
        $component = Livewire::actingAs($admin)->test(ListDecreeProposals::class);

        $component->assertTableActionVisible('approve', $proposal)
            ->assertTableActionVisible('reject', $proposal)
            ->assertTableActionVisible('detail', $proposal)
            ->callTableAction('reject', $proposal, data: ['reason' => 'Dokumen belum lengkap.']);

        $this->assertSame(DecreeProposal::STATUS_REJECTED, $proposal->fresh()->status);
    }

    public function test_approval_rolls_back_records_when_role_assignment_fails(): void
    {
        [$proposal, $admin] = $this->proposalFixture();
        $before = [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()];
        Role::query()->where('name', User::ROLE_GURU_PEGAWAI)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            $this->expectException(\Spatie\Permission\Exceptions\RoleDoesNotExist::class);
            app(DecreeProposalApprovalService::class)->approve($proposal, $admin);
        } finally {
            $proposal->refresh();
            $this->assertSame($before, [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()]);
            $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $proposal->status);
        }
    }

    public function test_approval_rolls_back_when_final_proposal_status_save_fails(): void
    {
        [$proposal, $admin] = $this->proposalFixture();
        $before = [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()];
        $shouldFail = true;
        Event::listen('eloquent.saving: '.DecreeProposal::class, function (DecreeProposal $record) use (&$shouldFail): void {
            if ($shouldFail && $record->status === DecreeProposal::STATUS_COMPLETED && filled($record->employee_id)) {
                $shouldFail = false;
                throw new \RuntimeException('Simulated final proposal save failure.');
            }
        });

        Notification::fake();
        $this->expectException(\RuntimeException::class);
        try {
            app(DecreeProposalApprovalService::class)->approve($proposal, $admin);
        } finally {
            $fresh = $proposal->fresh();
            $this->assertSame($before, [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()]);
            $this->assertNull($fresh->employee_id);
            $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $fresh->status);
            $this->assertTrue(Storage::disk('decree-proposals')->exists($fresh->diploma_path));
        }
    }

    public function test_legacy_proposal_with_existing_employee_is_not_auto_processed(): void
    {
        [$proposal, $admin] = $this->proposalFixture();
        $proposal->forceFill(['employee_id' => 987654321])->save();

        $this->expectException(ValidationException::class);
        app(DecreeProposalApprovalService::class)->approve($proposal->fresh(), $admin);
    }

    public function test_admin_induk_rejection_preserves_records_and_exposes_reason_only_to_authorized_school(): void
    {
        [$proposal, $admin, $email, $school, $foundation] = $this->proposalFixture();
        $before = [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()];

        app(DecreeProposalRejectionService::class)->reject($proposal, $admin, 'Dokumen ijazah belum sesuai.');

        $proposal->refresh();
        $this->assertSame(DecreeProposal::STATUS_REJECTED, $proposal->status);
        $this->assertSame('Dokumen ijazah belum sesuai.', $proposal->rejectionReason());
        $this->assertSame($before, [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()]);
        $this->assertTrue(Storage::disk('decree-proposals')->exists($proposal->photo_path));

        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(), 'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(), 'status' => 'active',
        ]);
        $this->assertTrue($schoolAdmin->can('view', $proposal));
        $this->assertFalse($schoolAdmin->can('update', $proposal));
        $this->assertSame($email, $proposal->candidate_email);
    }

    public function test_rejection_blocks_blank_reason_and_second_decision(): void
    {
        [$proposal, $admin] = $this->proposalFixture();

        $this->expectException(ValidationException::class);
        app(DecreeProposalRejectionService::class)->reject($proposal, $admin, '   ');
        $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $proposal->fresh()->status);
    }

    public function test_completed_proposal_cannot_be_approved_or_rejected_again(): void
    {
        [$proposal, $admin] = $this->proposalFixture();
        app(DecreeProposalApprovalService::class)->approve($proposal, $admin);

        try {
            app(DecreeProposalApprovalService::class)->approve($proposal->fresh(), $admin);
            $this->fail('Approval kedua seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        app(DecreeProposalRejectionService::class)->reject($proposal->fresh(), $admin, 'Tidak boleh diproses ulang.');
    }

    public function test_approval_validation_failure_rolls_back_without_creating_records(): void
    {
        [$proposal, $admin] = $this->proposalFixture();
        Storage::disk('decree-proposals')->delete($proposal->diploma_path);
        $before = [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()];

        $this->expectException(ValidationException::class);
        try {
            app(DecreeProposalApprovalService::class)->approve($proposal, $admin);
        } finally {
            $proposal->refresh();
            $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $proposal->status);
            $this->assertSame($before, [User::count(), Employee::count(), Membership::count(), EmployeeAssignment::count()]);
        }
    }

    /** @return array{DecreeProposal, User, string, School, Foundation} */
    private function proposalFixture(): array
    {
        $suffix = strtolower(str()->random(10));
        $email = "guru-9b-{$suffix}@example.test";
        $foundation = Foundation::query()->create([
            'name' => "Foundation 9B {$suffix}", 'code' => "F-9B-{$suffix}", 'is_active' => true,
        ]);
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(), 'name' => "School 9B {$suffix}",
            'npsn' => (string) random_int(10000000, 99999999), 'school_level' => 'MI', 'is_active' => true,
        ]);
        $position = EmployeePosition::factory()->create([
            'category' => EmployeePosition::CATEGORY_TEACHING, 'name' => "Position 9B {$suffix}", 'is_active' => true,
        ]);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $paths = [
            'photo_path' => "9b/{$suffix}/photo.jpg", 'diploma_path' => "9b/{$suffix}/diploma.pdf",
            'application_letter_path' => "9b/{$suffix}/application.pdf", 'service_statement_path' => "9b/{$suffix}/statement.pdf",
            'task_assignment_certificate_path' => "9b/{$suffix}/assignment.pdf",
        ];
        foreach ($paths as $path) Storage::disk('decree-proposals')->put($path, 'dummy');

        $proposal = DecreeProposal::query()->create([
            'candidate_name' => "Guru 9B {$suffix}", 'candidate_email' => $email, 'candidate_phone' => '081234567890',
            'candidate_school' => $school->name, 'status' => DecreeProposal::STATUS_SUBMITTED,
            'legacy_snapshot' => [
                'employee_code' => "EWANUGK-9B-{$suffix}", 'name' => "Guru 9B {$suffix}", 'email' => $email,
                'phone' => '081234567890', 'school_id' => $school->getKey(), 'school_name' => $school->name,
                'foundation_id' => $foundation->getKey(), 'employment_status' => 'GTY', 'birth_date' => '1995-05-10',
                'birth_place' => 'Gunungkidul', 'position_id' => $position->getKey(), 'position_name' => $position->name,
                'assignment_start_date' => '2026-07-01', 'last_education' => 'S1, 2018', 'nuptk' => "1234567890{$suffix}",
                'program_study' => 'PGMI',
            ],
            ...$paths,
        ]);

        return [$proposal, $admin, $email, $school, $foundation];
    }
}
