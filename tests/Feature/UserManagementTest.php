<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_create_user_with_one_role_from_compact_add_form(): void
    {
        Livewire::test(CreateUser::class)
            ->set('data.name', 'Admin Sekolah Baru')
            ->set('data.email', 'admin-sekolah-baru@example.test')
            ->set('data.password', 'password-baru')
            ->set('data.role_name', User::ROLE_ADMIN_SEKOLAH_MADRASAH)
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'admin-sekolah-baru@example.test')->firstOrFail();

        $this->assertSame('Admin Sekolah Baru', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password-baru', $user->password));
        $this->assertTrue($user->hasExactRoles([User::ROLE_ADMIN_SEKOLAH_MADRASAH]));
        $this->assertSame($this->admin->getKey(), $user->created_by);
        $this->assertSame($this->admin->getKey(), $user->updated_by);
        $this->assertCount(0, $user->memberships);
    }

    public function test_new_school_admin_is_automatically_connected_to_matching_school(): void
    {
        $foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Baleharjo',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        Livewire::test(CreateUser::class)
            ->set('data.name', 'MI YAPPI Baleharjo')
            ->set('data.email', 'admin-baleharjo@example.test')
            ->set('data.password', 'password-baru')
            ->set('data.role_name', User::ROLE_ADMIN_SEKOLAH_MADRASAH)
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'admin-baleharjo@example.test')->firstOrFail();

        $this->assertDatabaseHas('memberships', [
            'user_id' => $user->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_user_role_without_changing_password(): void
    {
        $user = User::factory()->create([
            'name' => 'User Lama',
            'email' => 'user-lama@example.test',
            'password' => 'password-lama',
            'is_active' => true,
        ]);
        $user->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $newRole = Role::findByName(User::ROLE_GURU_PEGAWAI, 'web');

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->set('data.name', 'User Diperbarui')
            ->set('data.roles', [$newRole->getKey()])
            ->set('data.password', '')
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('User Diperbarui', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password-lama', $user->password));
        $this->assertTrue($user->hasExactRoles([User::ROLE_GURU_PEGAWAI]));
        $this->assertSame($this->admin->getKey(), $user->updated_by);
    }

    public function test_password_is_blank_on_edit_and_can_be_changed_explicitly(): void
    {
        $user = User::factory()->create(['password' => 'password-lama']);
        $user->assignRole(User::ROLE_GURU_PEGAWAI);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertSet('data.password', null)
            ->set('data.password', 'password-baru')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }

    public function test_admin_cannot_deactivate_or_change_its_own_role(): void
    {
        $schoolAdminRole = Role::findByName(User::ROLE_ADMIN_SEKOLAH_MADRASAH, 'web');

        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->set('data.roles', [$schoolAdminRole->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->admin->refresh();

        $this->assertTrue($this->admin->is_active);
        $this->assertTrue($this->admin->hasExactRoles([User::ROLE_ADMIN_INDUK]));
    }

    public function test_inactive_user_cannot_access_any_panel(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $inactiveUser->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($inactiveUser);

        $this->get('/admin')->assertForbidden();
        $this->get('/app')->assertForbidden();
    }

    public function test_add_admin_form_validates_unique_email_password_and_role(): void
    {
        User::factory()->create(['email' => 'sudah-ada@example.test']);

        Livewire::test(CreateUser::class)
            ->set('data.name', '')
            ->set('data.email', 'sudah-ada@example.test')
            ->set('data.password', 'pendek')
            ->set('data.role_name', null)
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'email' => 'unique',
                'password' => 'min',
                'role_name' => 'required',
            ]);
    }

    public function test_admin_table_matches_required_columns_and_searches_phone_number(): void
    {
        $user = User::factory()->create([
            'name' => 'Muhamad Ihsan Prakoso',
            'email' => 'muhamad.ihsan@example.test',
            'phone_number' => '088215927491',
        ]);
        $user->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        Livewire::test(ListUsers::class)
            ->assertSee('Admin')
            ->assertSee('Image')
            ->assertSee('Nama Lengkap')
            ->assertSee('Nomor Telepon')
            ->assertSee('Status')
            ->assertCanSeeTableRecords([$user])
            ->searchTable('088215927491')
            ->assertCanSeeTableRecords([$user])
            ->assertSee('Muhamad Ihsan Prakoso');
    }

    public function test_admin_can_toggle_other_user_status_from_table(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(User::ROLE_GURU_PEGAWAI);

        Livewire::test(ListUsers::class)
            ->call('updateTableColumnState', 'is_active', (string) $user->getKey(), false);

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertSame($this->admin->getKey(), $user->updated_by);
    }

    public function test_admin_can_delete_another_user_but_not_its_own_account(): void
    {
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_GURU_PEGAWAI);

        Livewire::test(ListUsers::class)
            ->assertTableActionVisible(DeleteAction::class, $user)
            ->assertTableActionHidden(DeleteAction::class, $this->admin)
            ->callTableAction(DeleteAction::class, $user);

        $this->assertDatabaseMissing('users', ['id' => $user->getKey()]);
        $this->assertDatabaseHas('users', ['id' => $this->admin->getKey()]);
    }

    public function test_add_admin_form_matches_required_compact_layout(): void
    {
        Livewire::test(CreateUser::class)
            ->assertSee('Tambah Admin')
            ->assertSee('NAMA USERS')
            ->assertSee('EMAIL ADMIN MADRASAH/SEKOLAH')
            ->assertSee('PASSWORD')
            ->assertSee('ROLE')
            ->assertSee('Simpan')
            ->assertSee('Kembali')
            ->assertFormFieldVisible('name')
            ->assertFormFieldVisible('email')
            ->assertFormFieldVisible('password')
            ->assertFormFieldVisible('role_name')
            ->assertFormFieldDoesNotExist('avatar_path')
            ->assertFormFieldDoesNotExist('phone_number')
            ->assertFormFieldDoesNotExist('password_confirmation')
            ->assertFormFieldDoesNotExist('is_active')
            ->assertFormFieldHidden('roles')
            ->assertFormFieldDoesNotExist('memberships')
            ->assertDontSee('Foto Admin')
            ->assertDontSee('Konfirmasi Password')
            ->assertDontSee('Status Aktif')
            ->assertDontSee('Penugasan Yayasan dan Sekolah');
    }
}
