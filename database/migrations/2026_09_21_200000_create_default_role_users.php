<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private array $accounts = [
        ['role' => 'admin-induk', 'name' => 'Admin Induk', 'email' => 'admin.induk@yayasan.local', 'password' => 'AdminInduk#2026'],
        ['role' => 'admin-sekolah-madrasah', 'name' => 'Admin Sekolah Madrasah', 'email' => 'admin.sekolah@yayasan.local', 'password' => 'AdminSekolah#2026'],
        ['role' => 'guru-pegawai', 'name' => 'Guru Pegawai', 'email' => 'guru.pegawai@yayasan.local', 'password' => 'GuruPegawai#2026'],
        ['role' => 'pengurus', 'name' => 'Pengurus', 'email' => 'pengurus@yayasan.local', 'password' => 'Pengurus#2026'],
    ];

    public function up(): void
    {
        foreach ($this->accounts as $account) {
            Role::firstOrCreate(['name' => $account['role'], 'guard_name' => 'web']);
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => Hash::make($account['password']), 'is_active' => true]
            );

            $user->syncRoles([$account['role']]);
        }
    }

    public function down(): void
    {
        foreach ($this->accounts as $account) {
            $user = User::where('email', $account['email'])->first();
            if ($user) {
                $user->syncRoles([]);
                $user->delete();
            }
        }
    }
};
