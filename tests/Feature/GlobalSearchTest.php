<?php

namespace Tests\Feature;

use App\Filament\GlobalSearch\NavigationSearchProvider;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_global_search_finds_core_records_by_useful_attributes(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create(['name' => 'Administrator Pusat', 'email' => 'pusat@example.test']);
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $foundation = Foundation::factory()->create();
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI Pencarian Terpadu',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'name' => 'Ahmad Pencarian',
            'employee_code' => 'PEG-CARI-001',
        ]);

        $this->assertCount(1, EmployeeResource::getGlobalSearchResults('PEG-CARI-001'));
        $this->assertCount(1, SchoolResource::getGlobalSearchResults('12345678'));
        $this->assertCount(1, UserResource::getGlobalSearchResults('pusat@example.test'));
    }

    public function test_global_search_field_uses_indonesian_locale(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('placeholder="Cari"', false);
    }

    public function test_admin_navigation_search_is_case_insensitive_and_uses_aliases(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $lowercaseResults = app(NavigationSearchProvider::class)
            ->getResults('guru')
            ->getCategories()
            ->get('Menu dan Halaman');
        $uppercaseResults = app(NavigationSearchProvider::class)
            ->getResults('GURU')
            ->getCategories()
            ->get('Menu dan Halaman');

        $this->assertContains('Guru dan Pegawai', $lowercaseResults->pluck('title'));
        $this->assertSame($lowercaseResults->pluck('title')->all(), $uppercaseResults->pluck('title')->all());
        $this->assertLessThanOrEqual(10, $lowercaseResults->count());
        $this->assertStringContainsString('/admin/employees', $lowercaseResults->firstWhere('title', 'Guru dan Pegawai')->url);
    }

    public function test_navigation_search_only_exposes_items_visible_to_the_current_role(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->seed(RolePermissionSeeder::class);
        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->actingAs($teacher);

        $paymentResults = app(NavigationSearchProvider::class)
            ->getResults('pembayaran')
            ->getCategories()
            ->get('Menu dan Halaman');
        $administrationResults = app(NavigationSearchProvider::class)
            ->getResults('administrasi')
            ->getCategories()
            ->get('Menu dan Halaman');

        $this->assertContains('Pembayaran', $paymentResults->pluck('title'));
        $this->assertTrue($administrationResults->isEmpty());
        $this->assertTrue($paymentResults->every(fn ($result): bool => str_starts_with($result->url, '/app')));
    }

    public function test_administration_and_institution_parents_only_toggle_their_submenus(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);
        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $adminItems = collect(filament()->getNavigation())->flatMap->getItems()->keyBy->getLabel();

        $this->assertNull($adminItems->get('Administrasi')->getUrl());
        $this->assertNull($adminItems->get('Kelembagaan')->getUrl());

        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($schoolAdmin);
        filament()->setCurrentPanel(filament()->getPanel('app'));

        $appItems = collect(filament()->getNavigation())->flatMap->getItems()->keyBy->getLabel();

        $this->assertNull($appItems->get('Administrasi')->getUrl());
        $this->assertNull($appItems->get('Kelembagaan')->getUrl());
    }
}
