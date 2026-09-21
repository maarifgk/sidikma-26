<?php

namespace Tests\Feature;

use App\Filament\Resources\SchoolOrigins\Pages\CreateSchoolOrigin;
use App\Filament\Resources\SchoolOrigins\Pages\ListSchoolOrigins;
use App\Filament\Resources\SchoolOrigins\SchoolOriginResource;
use App\Models\SchoolOrigin;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolOriginResourceTest extends TestCase
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

    public function test_admin_can_open_origin_list_and_add_page(): void
    {
        $this->get(SchoolOriginResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Asal Madrasah')
            ->assertSee('Add');

        $this->get(SchoolOriginResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tambah Asal Madrasah');
    }

    public function test_add_origin_form_matches_reference_and_saves_data(): void
    {
        Livewire::test(CreateSchoolOrigin::class)
            ->assertSee('Tambah Asal Madrasah')
            ->assertSee('NAMA ASAL MADRASAH/SEKOLAH')
            ->assertSee('KETERANGAN')
            ->assertSee('Masukan Nama Madrasah/Sekolah')
            ->assertSee('Masukan Keterangan Jenjang')
            ->assertSee('Simpan')
            ->assertSee('Kembali')
            ->set('data.name', 'MI YAPPI Baleharjo')
            ->set('data.description', 'Jenjang MI')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('school_origins', [
            'name' => 'MI YAPPI Baleharjo',
            'description' => 'Jenjang MI',
        ]);
    }

    public function test_origin_name_is_required_and_unique(): void
    {
        SchoolOrigin::query()->create([
            'name' => 'MTs Contoh',
            'description' => 'Jenjang MTs',
        ]);

        Livewire::test(CreateSchoolOrigin::class)
            ->set('data.name', 'MTs Contoh')
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);

        Livewire::test(CreateSchoolOrigin::class)
            ->set('data.name', '')
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_origin_list_is_searchable(): void
    {
        $found = SchoolOrigin::query()->create([
            'name' => 'SMP Maarif Gunungkidul',
            'description' => 'Jenjang SMP',
        ]);
        $hidden = SchoolOrigin::query()->create([
            'name' => 'MI Lain',
            'description' => 'Jenjang MI',
        ]);

        Livewire::test(ListSchoolOrigins::class)
            ->searchTable('Gunungkidul')
            ->assertCanSeeTableRecords([$found])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_operational_user_cannot_access_origin_master(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($schoolAdmin);

        $this->get(SchoolOriginResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertForbidden();
    }
}
