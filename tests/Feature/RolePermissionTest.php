<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_role_permission_seeder_creates_the_initial_access_structure(): void
    {
        $this->assertSame(4, Role::query()->count());
        $this->assertSame(59, Permission::query()->count());

        $adminInduk = Role::findByName('admin-induk', 'web');
        $adminSekolahMadrasah = Role::findByName('admin-sekolah-madrasah', 'web');
        $guruPegawai = Role::findByName('guru-pegawai', 'web');
        $pengurus = Role::findByName('pengurus', 'web');

        $this->assertCount(0, $pengurus->permissions);

        $this->assertCount(59, $adminInduk->permissions);
        $this->assertEqualsCanonicalizing(
            [
                'foundation.view',
                'school.view',
                'school.update',
                'employee.view',
                'employee.create',
                'employee.update',
                'document.view',
                'document.download',
                'document.create',
                'approval.view',
                'approval.create',
                'approval.submit',
                'attendance.view',
                'attendance.manage',
                'attendance.leave.review',
                'attendance.settings',
                'decree-submission.view',
                'decree-submission.create',
                'decree-correction.view',
                'decree-correction.create',
                'decree-correction.update',
            ],
            $adminSekolahMadrasah->permissions->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['foundation.view', 'school.view', 'attendance.mark', 'attendance.leave.request'],
            $guruPegawai->permissions->pluck('name')->all(),
        );
    }

    public function test_admin_induk_user_inherits_all_initial_permissions(): void
    {
        $user = User::factory()->create();

        $user->assignRole('admin-induk');

        $this->assertTrue($user->hasRole('admin-induk'));
        $this->assertCount(59, $user->getAllPermissions());
        $this->assertTrue($user->hasPermissionTo('foundation.view'));
        $this->assertTrue($user->hasPermissionTo('employee.delete'));
        $this->assertTrue($user->hasPermissionTo('position.delete'));
        $this->assertTrue($user->hasPermissionTo('assignment.delete'));
        $this->assertTrue($user->hasPermissionTo('document.download'));
        $this->assertTrue($user->hasPermissionTo('approval.approve'));
        $this->assertTrue($user->hasPermissionTo('audit.view'));
        $this->assertTrue($user->hasPermissionTo('attendance.settings'));
        $this->assertTrue($user->hasPermissionTo('decree-correction.update'));
    }
}
