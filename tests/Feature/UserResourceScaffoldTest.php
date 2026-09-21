<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceScaffoldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_induk_can_open_user_list(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Induk',
            'email' => 'admin-induk@example.test',
        ]);
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $otherUser = User::factory()->create([
            'name' => 'User Operasional',
            'email' => 'operasional@example.test',
        ]);
        $this->actingAs($admin);

        $this->get(UserResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee($otherUser->name);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Admin');
    }

    public function test_admin_induk_can_open_user_create_and_edit_pages(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $record = User::factory()->create();
        $this->actingAs($admin);

        $this->get(UserResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk();
        $this->get(UserResource::getUrl(
            'edit',
            ['record' => $record],
            panel: 'admin',
            isAbsolute: false,
        ))->assertOk();

        $this->assertTrue(UserResource::canCreate());
        $this->assertTrue(UserResource::canEdit($record));
        $this->assertTrue(UserResource::canDelete($record));
        $this->assertFalse(UserResource::canDelete($admin));
    }

    public function test_operational_roles_and_app_panel_cannot_open_user_resource(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($schoolAdmin);

        $this->get(UserResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertForbidden();
        $this->get('/app/users')->assertNotFound();
    }
}
