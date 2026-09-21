<?php

namespace Tests\Feature;

use App\Filament\Auth\Pages\IntegratedLogin;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_both_panel_login_pages_are_available(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/app/login')->assertOk();
    }

    public function test_admin_induk_is_redirected_from_operational_to_admin_panel(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);

        $this->actingAs($admin);

        $this->get('/admin')->assertOk();
        $this->get('/app')->assertRedirect('/admin');
    }

    public function test_school_admin_only_accesses_operational_panel(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        $this->actingAs($schoolAdmin);

        $this->get('/admin')->assertForbidden();
        $this->get('/app')->assertOk();
    }

    public function test_teacher_only_accesses_operational_panel(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);

        $this->actingAs($teacher);

        $this->get('/admin')->assertForbidden();
        $this->get('/app')->assertOk();
    }

    public function test_user_without_an_official_role_cannot_access_either_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get('/admin')->assertForbidden();
        $this->get('/app')->assertForbidden();
    }

    public function test_guest_is_redirected_to_the_operational_login_page(): void
    {
        $this->get('/app')->assertRedirect('/app/login');
    }

    public function test_school_admin_can_login_from_admin_address_and_is_redirected_to_operational_panel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));

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
        $schoolAdmin = User::factory()->create([
            'name' => 'Admin MI YAPPI Baleharjo',
            'email' => 'admin-baleharjo@example.test',
            'password' => 'password-madrasah',
            'is_active' => true,
        ]);
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        Livewire::test(IntegratedLogin::class)
            ->set('data.email', 'admin-baleharjo@example.test')
            ->set('data.password', 'password-madrasah')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(url('/app'));

        $this->assertAuthenticatedAs($schoolAdmin);
        $this->assertDatabaseHas('memberships', [
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);
    }

    public function test_admin_induk_login_from_operational_address_is_redirected_to_admin_panel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('app'));

        $admin = User::factory()->create([
            'email' => 'admin-induk@example.test',
            'password' => 'password-admin-induk',
            'is_active' => true,
        ]);
        $admin->assignRole(User::ROLE_ADMIN_INDUK);

        Livewire::test(IntegratedLogin::class)
            ->set('data.email', 'admin-induk@example.test')
            ->set('data.password', 'password-admin-induk')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(url('/admin'));

        $this->assertAuthenticatedAs($admin);
    }
}
