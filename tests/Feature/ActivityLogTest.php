<?php

namespace Tests\Feature;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\Activity;
use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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

        Activity::query()->delete();
    }

    public function test_activity_log_table_and_secure_configuration_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('activity_log', [
            'id',
            'log_name',
            'description',
            'subject_type',
            'subject_id',
            'causer_type',
            'causer_id',
            'properties',
            'event',
            'batch_uuid',
            'created_at',
            'updated_at',
        ]));
        $this->assertTrue(config('activitylog.enabled'));
        $this->assertTrue(config('activitylog.subject_returns_soft_deleted_models'));
        $this->assertSame(Activity::class, config('activitylog.activity_model'));
        $this->assertSame(365, config('activitylog.delete_records_older_than_days'));
    }

    public function test_user_changes_are_logged_without_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Pengguna Lama',
            'email' => 'audit-user@example.test',
            'password' => 'password-rahasia',
        ]);

        $created = Activity::query()
            ->where('log_name', 'user')
            ->where('event', 'created')
            ->where('subject_id', $user->getKey())
            ->sole();

        $this->assertTrue($created->causer->is($this->admin));
        $this->assertSame('audit-user@example.test', $created->newValues()['email']);
        $this->assertArrayNotHasKey('password', $created->newValues());

        $user->update([
            'name' => 'Pengguna Baru',
            'email' => 'audit-user-baru@example.test',
        ]);

        $updated = Activity::query()
            ->where('log_name', 'user')
            ->where('event', 'updated')
            ->where('subject_id', $user->getKey())
            ->sole();

        $this->assertSame('Pengguna Lama', $updated->oldValues()['name']);
        $this->assertSame('Pengguna Baru', $updated->newValues()['name']);
        $this->assertSame('audit-user@example.test', $updated->oldValues()['email']);
        $this->assertSame('audit-user-baru@example.test', $updated->newValues()['email']);
        $this->assertArrayNotHasKey('password', $updated->oldValues());
        $this->assertArrayNotHasKey('password', $updated->newValues());
    }

    public function test_school_and_employee_lifecycle_is_logged(): void
    {
        $school = $this->createSchool();
        $employee = Employee::factory()->create([
            'foundation_id' => $school->foundation_id,
            'school_id' => $school->getKey(),
        ]);
        $employee->update(['name' => 'Nama Pegawai Diperbarui']);
        $school->delete();
        $school->restore();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'school',
            'event' => 'created',
            'subject_type' => School::class,
            'subject_id' => $school->getKey(),
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'school',
            'event' => 'deleted',
            'subject_type' => School::class,
            'subject_id' => $school->getKey(),
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'school',
            'event' => 'restored',
            'subject_type' => School::class,
            'subject_id' => $school->getKey(),
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'employee',
            'event' => 'updated',
            'subject_type' => Employee::class,
            'subject_id' => $employee->getKey(),
        ]);
    }

    public function test_approval_transition_uses_explicit_actor_as_causer(): void
    {
        $request = ApprovalRequest::factory()->create();
        $submitter = User::factory()->create(['name' => 'Pengaju Approval']);
        Activity::query()->delete();

        $request->submit($submitter, 'Mohon diverifikasi.');

        $activity = Activity::query()
            ->where('log_name', 'approval')
            ->where('event', ApprovalRequest::STATUS_SUBMITTED)
            ->sole();

        $this->assertTrue($activity->subject->is($request));
        $this->assertTrue($activity->causer->is($submitter));
        $this->assertSame(ApprovalRequest::STATUS_DRAFT, $activity->oldValues()['status']);
        $this->assertSame(ApprovalRequest::STATUS_SUBMITTED, $activity->newValues()['status']);
        $this->assertSame('Mohon diverifikasi.', $activity->newValues()['notes']);
    }

    public function test_role_permission_and_assignment_changes_are_logged(): void
    {
        $role = Role::create(['name' => 'auditor-test', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'audit.test', 'guard_name' => 'web']);
        $user = User::factory()->create();

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authorization',
            'event' => 'created',
            'subject_type' => Role::class,
            'subject_id' => $role->getKey(),
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authorization',
            'event' => 'created',
            'subject_type' => Permission::class,
            'subject_id' => $permission->getKey(),
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authorization',
            'event' => 'permission_attached',
            'subject_type' => Role::class,
            'subject_id' => $role->getKey(),
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authorization',
            'event' => 'role_attached',
            'subject_type' => User::class,
            'subject_id' => $user->getKey(),
            'causer_id' => $this->admin->getKey(),
        ]);
    }

    public function test_admin_can_view_and_filter_read_only_audit_resource(): void
    {
        $school = $this->createSchool();
        $employee = Employee::factory()->create();
        $activity = Activity::query()->where('subject_type', School::class)->firstOrFail();

        $this->get(ActivityResource::getUrl('index', panel: 'admin'))
            ->assertOk()
            ->assertSee('Audit Log')
            ->assertSee('Sekolah');

        $this->get(ActivityResource::getUrl('view', ['record' => $activity], panel: 'admin'))
            ->assertOk()
            ->assertSee($activity->description)
            ->assertSee($this->admin->name);

        Livewire::test(ListActivities::class)
            ->filterTable('log_name', 'school')
            ->assertCanSeeTableRecords(
                Activity::query()->where('log_name', 'school')->get(),
            )
            ->assertCanNotSeeTableRecords(
                Activity::query()->where('log_name', 'employee')->get(),
            );

        Livewire::test(ListActivities::class)
            ->filterTable('subject_type', Employee::class)
            ->assertCanSeeTableRecords(
                Activity::query()->where('subject_type', Employee::class)->get(),
            )
            ->assertCanNotSeeTableRecords(
                Activity::query()->where('subject_type', School::class)->get(),
            );

        $this->assertFalse(Gate::forUser($this->admin)->allows('create', Activity::class));
        $this->assertFalse(Gate::forUser($this->admin)->allows('update', $activity));
        $this->assertFalse(Gate::forUser($this->admin)->allows('delete', $activity));
        $this->assertTrue($school->exists);
        $this->assertTrue($employee->exists);
    }

    public function test_non_admin_induk_cannot_access_audit_resource(): void
    {
        $activity = activity('system')->event('test')->log('Audit akses');
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        $this->actingAs($schoolAdmin);

        $this->get(ActivityResource::getUrl('index', panel: 'admin'))->assertForbidden();
        $this->get(ActivityResource::getUrl('view', ['record' => $activity], panel: 'admin'))
            ->assertForbidden();
    }

    private function createSchool(): School
    {
        $foundation = Foundation::factory()->create();

        return School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'Sekolah Audit',
            'npsn' => '24681357',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }
}
