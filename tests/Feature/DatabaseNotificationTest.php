<?php

namespace Tests\Feature;

use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\ApplicationNotificationService;
use App\Services\ApprovalWorkflow;
use Database\Seeders\RolePermissionSeeder;
use Filament\Livewire\DatabaseNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseNotificationTest extends TestCase
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

        $this->admin->notifications()->delete();
    }

    public function test_notification_table_and_both_panel_notification_centers_are_enabled(): void
    {
        $this->assertTrue(Schema::hasColumns('notifications', [
            'id',
            'type',
            'notifiable_type',
            'notifiable_id',
            'data',
            'read_at',
            'created_at',
            'updated_at',
        ]));
        $this->assertTrue(filament()->getPanel('admin')->hasDatabaseNotifications());
        $this->assertTrue(filament()->getPanel('app')->hasDatabaseNotifications());
    }

    public function test_new_approval_notifies_all_active_admin_induk_users(): void
    {
        $secondAdmin = User::factory()->create();
        $secondAdmin->assignRole(User::ROLE_ADMIN_INDUK);
        $secondAdmin->notifications()->delete();

        $request = app(ApprovalWorkflow::class)->createAndSubmit(
            Employee::factory()->create(['name' => 'Pegawai Pengajuan']),
            $this->admin,
            'Mohon segera diperiksa.',
        );

        foreach ([$this->admin, $secondAdmin] as $recipient) {
            $notification = $recipient->unreadNotifications()->sole();

            $this->assertSame('filament', $notification->data['format']);
            $this->assertSame('Pengajuan approval baru', $notification->data['title']);
            $this->assertStringContainsString('Pegawai Pengajuan', $notification->data['body']);
            $this->assertNotEmpty($notification->data['actions']);
        }

        $this->assertSame(ApprovalRequest::STATUS_SUBMITTED, $request->status);
    }

    public function test_approval_decision_notifies_submitter_for_approved_and_rejected_status(): void
    {
        $approved = app(ApprovalWorkflow::class)->createAndSubmit(
            Employee::factory()->create(['name' => 'Pegawai Disetujui']),
            $this->admin,
        );
        $this->admin->notifications()->delete();

        $approved->verify($this->admin)->approve($this->admin, 'Data sudah sesuai.');

        $approvedNotification = $this->admin->unreadNotifications()->sole();
        $this->assertSame('Pengajuan disetujui', $approvedNotification->data['title']);
        $this->assertStringContainsString('Pegawai Disetujui', $approvedNotification->data['body']);

        $this->admin->notifications()->delete();
        $rejected = app(ApprovalWorkflow::class)->createAndSubmit(
            Employee::factory()->create(['name' => 'Pegawai Ditolak']),
            $this->admin,
        );
        $this->admin->notifications()->delete();

        $rejected->reject($this->admin, 'Lampiran belum lengkap.');

        $rejectedNotification = $this->admin->unreadNotifications()->sole();
        $this->assertSame('Pengajuan ditolak', $rejectedNotification->data['title']);
        $this->assertStringContainsString('Lampiran belum lengkap.', $rejectedNotification->data['body']);
    }

    public function test_admin_can_send_incomplete_document_reminder_to_employee_user(): void
    {
        $employeeUser = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->getKey(),
            'name' => 'Guru Penerima Pengingat',
        ]);
        $employeeUser->notifications()->delete();

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ])
            ->callTableAction('remindIncompleteDocuments', data: [
                'notes' => 'Ijazah dan SK pengangkatan belum tersedia.',
            ])
            ->assertHasNoFormErrors();

        $notification = $employeeUser->unreadNotifications()->sole();

        $this->assertSame('Dokumen belum lengkap', $notification->data['title']);
        $this->assertStringContainsString('Ijazah dan SK pengangkatan', $notification->data['body']);
        $this->assertStringContainsString('Guru Penerima Pengingat', $notification->data['body']);
    }

    public function test_important_account_and_role_changes_notify_affected_user(): void
    {
        $target = User::factory()->create([
            'email' => 'akun-lama@example.test',
        ]);
        $target->notifications()->delete();

        $target->update([
            'email' => 'akun-baru@example.test',
            'password' => 'password-baru',
        ]);

        $this->assertCount(1, $target->unreadNotifications()->get(), 'Perubahan akun menghasilkan notifikasi ganda.');
        $accountNotification = $target->unreadNotifications()->sole();
        $this->assertSame('Perubahan akun penting', $accountNotification->data['title']);
        $this->assertStringContainsString('email, password', $accountNotification->data['body']);
        $this->assertStringNotContainsString('password-baru', $accountNotification->data['body']);

        $target->notifications()->delete();
        $target->assignRole(User::ROLE_GURU_PEGAWAI);

        $this->assertCount(1, $target->unreadNotifications()->get(), 'Perubahan role menghasilkan notifikasi ganda.');
        $roleNotification = $target->unreadNotifications()->sole();
        $this->assertSame('Perubahan akun penting', $roleNotification->data['title']);
        $this->assertStringContainsString('role', $roleNotification->data['body']);
        $this->assertStringContainsString($this->admin->name, $roleNotification->data['body']);
    }

    public function test_notification_can_be_marked_read_but_not_by_another_user(): void
    {
        app(ApplicationNotificationService::class)->notifyImportantAccountChange(
            $this->admin,
            ['email'],
            $this->admin,
        );
        $notification = $this->admin->unreadNotifications()->sole();

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);
        filament()->setCurrentPanel(filament()->getPanel('app'));

        Livewire::test(DatabaseNotifications::class)
            ->call('markNotificationAsRead', $notification->getKey());

        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($this->admin);
        filament()->setCurrentPanel(filament()->getPanel('admin'));

        Livewire::test(DatabaseNotifications::class)
            ->call('markNotificationAsRead', $notification->getKey());

        $this->assertNotNull($notification->fresh()->read_at);

        Livewire::test(DatabaseNotifications::class)
            ->call('markNotificationAsUnread', $notification->getKey());

        $this->assertNull($notification->fresh()->read_at);
    }
}
