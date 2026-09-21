<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's initial roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'foundation.view',
            'foundation.create',
            'foundation.update',
            'foundation.delete',
            'school.view',
            'school.create',
            'school.update',
            'school.delete',
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'role.view',
            'role.assign',
            'permission.view',
            'permission.assign',
            'membership.view',
            'membership.create',
            'membership.update',
            'membership.delete',
            'employee.view',
            'employee.create',
            'employee.update',
            'employee.delete',
            'position.view',
            'position.create',
            'position.update',
            'position.delete',
            'assignment.view',
            'assignment.create',
            'assignment.update',
            'assignment.delete',
            'document.view',
            'document.download',
            'document.create',
            'document.update',
            'document.delete',
            'approval.view',
            'approval.create',
            'approval.update',
            'approval.delete',
            'approval.submit',
            'approval.verify',
            'approval.approve',
            'approval.reject',
            'audit.view',
            'attendance.view',
            'attendance.manage',
            'attendance.mark',
            'sk-yayasan.view',
            'sk-yayasan.manage',
            'attendance.leave.request',
            'attendance.leave.review',
            'attendance.settings',
            'decree-submission.view',
            'decree-submission.create',
            'decree-submission.update',
            'decree-submission.delete',
            'decree-correction.view',
            'decree-correction.create',
            'decree-correction.update',
        ])->map(
            fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'),
        );

        $adminInduk = Role::findOrCreate('admin-induk', 'web');
        $adminSekolahMadrasah = Role::findOrCreate('admin-sekolah-madrasah', 'web');
        $guruPegawai = Role::findOrCreate('guru-pegawai', 'web');
        Role::findOrCreate('pengurus', 'web');

        $adminInduk->syncPermissions($permissions);
        $adminSekolahMadrasah->syncPermissions(
            $permissions->whereIn('name', [
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
                'sk-yayasan.view',
                'sk-yayasan.manage',
                'attendance.mark',
                'attendance.leave.request',
                'decree-submission.view',
                'decree-submission.create',
                'decree-correction.view',
                'decree-correction.create',
                'decree-correction.update',
            ]),
        );
        $guruPegawai->syncPermissions(
            $permissions->whereIn('name', [
                'foundation.view',
                'school.view',
                'attendance.mark',
                'attendance.leave.request',
            ]),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
