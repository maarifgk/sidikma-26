<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\InstitutionProfile;
use App\Models\Foundation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InstitutionProfileNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);
    }

    public function test_profile_lembaga_navigation_and_all_submenus_are_visible(): void
    {
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Profile Lembaga');

        $this->get(InstitutionProfile::getUrl(
            ['section' => InstitutionProfile::SECTION_IDENTITY],
            panel: 'admin',
            isAbsolute: false,
        ))
            ->assertOk()
            ->assertSee('Identitas Lembaga')
            ->assertSee('Struktur Pengurus')
            ->assertSee('Program Kerja')
            ->assertSee('Laporan Tahunan');
    }

    public function test_every_profile_lembaga_section_can_be_opened(): void
    {
        foreach (InstitutionProfile::sectionLabels() as $section => $label) {
            $this->get(InstitutionProfile::getUrl(
                ['section' => $section],
                panel: 'admin',
                isAbsolute: false,
            ))
                ->assertOk()
                ->assertSee($label);
        }
    }

    public function test_identity_can_be_edited_from_the_header_action(): void
    {
        Livewire::test(InstitutionProfile::class)
            ->assertActionVisible('editIdentity')
            ->callAction('editIdentity', data: [
                'name' => "Lembaga Pendidikan Ma'arif NU PCNU Gunungkidul",
                'address' => 'Jl. Tentara Pelajar, Wonosari, Gunungkidul',
                'email' => 'maarifgunungkidul@gmail.com',
                'instagram' => '@lpm_gunungkidul',
                'phone' => '088215927491',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('foundations', [
            'name' => "Lembaga Pendidikan Ma'arif NU PCNU Gunungkidul",
            'email' => 'maarifgunungkidul@gmail.com',
            'instagram' => '@lpm_gunungkidul',
            'phone' => '088215927491',
        ]);
    }

    public function test_banner_can_be_uploaded_and_deleted(): void
    {
        Storage::fake('public');

        $component = Livewire::test(InstitutionProfile::class)
            ->assertActionVisible('uploadBanner')
            ->callAction('uploadBanner', data: [
                'banner_path' => UploadedFile::fake()->image('banner-lembaga.jpg', 1600, 400),
            ])
            ->assertHasNoActionErrors();

        $foundation = Foundation::query()->sole();
        $bannerPath = $foundation->banner_path;

        $this->assertNotNull($bannerPath);
        Storage::disk('public')->assertExists($bannerPath);

        $component
            ->assertActionVisible('deleteBanner')
            ->callAction('deleteBanner')
            ->assertHasNoActionErrors();

        $this->assertNull($foundation->fresh()->banner_path);
        Storage::disk('public')->assertMissing($bannerPath);
    }

    public function test_board_structure_can_be_added_edited_reordered_and_deleted(): void
    {
        $component = Livewire::test(InstitutionProfile::class)
            ->set('section', InstitutionProfile::SECTION_STRUCTURE)
            ->assertActionVisible('editStructure')
            ->callAction('editStructure', data: [
                'board_heading' => "Susunan Pengurus LP Ma'arif NU Gunungkidul",
                'board_term' => '2026-2031',
                'members' => [
                    ['position' => 'Penasihat I', 'name' => 'KH. Ahmad'],
                    ['position' => 'Ketua I', 'name' => 'Drs. Hasan'],
                    ['position' => 'SEKSI-SEKSI', 'name' => null],
                ],
            ])
            ->assertHasNoActionErrors();

        $foundation = Foundation::query()->sole();

        $this->assertSame('2026-2031', $foundation->fresh()->board_term);
        $this->assertDatabaseHas('foundation_board_members', [
            'foundation_id' => $foundation->getKey(),
            'position' => 'Penasihat I',
            'name' => 'KH. Ahmad',
            'sort_order' => 1,
        ]);

        $component
            ->callAction('editStructure', data: [
                'board_heading' => "Susunan Pengurus LP Ma'arif NU Gunungkidul",
                'board_term' => '2027-2032',
                'members' => [
                    ['position' => 'Ketua Umum', 'name' => 'Drs. Hasan, M.Pd.'],
                    ['position' => 'Penasihat Utama', 'name' => 'KH. Ahmad'],
                ],
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseCount('foundation_board_members', 2);
        $this->assertDatabaseMissing('foundation_board_members', [
            'position' => 'SEKSI-SEKSI',
        ]);
        $this->assertDatabaseHas('foundation_board_members', [
            'position' => 'Ketua Umum',
            'name' => 'Drs. Hasan, M.Pd.',
            'sort_order' => 1,
        ]);

        $this->get(InstitutionProfile::getUrl(
            ['section' => InstitutionProfile::SECTION_STRUCTURE],
            panel: 'admin',
            isAbsolute: false,
        ))
            ->assertOk()
            ->assertSee('Masa Jabatan 2027-2032')
            ->assertSee('Ketua Umum')
            ->assertSee('Drs. Hasan, M.Pd.');
    }
}
