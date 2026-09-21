<?php

namespace Tests\Feature;

use App\Filament\Resources\EducatorRecaps\EducatorRecapResource;
use App\Filament\Resources\EducatorRecaps\Pages\CreateEducatorRecap;
use App\Filament\Resources\EducatorRecaps\Pages\EditEducatorRecap;
use App\Filament\Resources\EducatorRecaps\Pages\ListEducatorRecaps;
use App\Models\EducatorRecap;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EducatorRecapResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($this->admin);

        $foundation = Foundation::query()->create([
            'name' => 'LP Ma\'arif NU Gunungkidul',
            'code' => 'LP-MAARIF-GK',
            'is_active' => true,
        ]);
        $this->school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Batusari',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Mulusan',
            'npsn' => '87654321',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_educator_recap_page_and_create_form(): void
    {
        $currentYear = EducatorRecap::currentAcademicYear();

        $this->get(EducatorRecapResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Data Jumlah Tenaga Pendidik per Tahun Pelajaran')
            ->assertSee('Total Tenaga (rekap)')
            ->assertSee('Madrasah Sudah Mengisi')
            ->assertSee('Madrasah Belum Mengisi')
            ->assertSee('Total Madrasah')
            ->assertSee($currentYear)
            ->assertSee('Tambah Data')
            ->assertSee('ASN Sertifikasi')
            ->assertSee('Yayasan Sertifikasi/Inpassing');

        $this->get(EducatorRecapResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tambah Data Jumlah Tenaga Pendidik')
            ->assertSee('TAHUN PELAJARAN')
            ->assertSee('SEKOLAH/MADRASAH')
            ->assertSee('YAYASAN NON-SERTIFIKASI');
    }

    public function test_admin_can_create_and_edit_educator_recap_with_automatic_total(): void
    {
        $year = EducatorRecap::currentAcademicYear();

        Livewire::test(CreateEducatorRecap::class)
            ->fillForm([
                'academic_year' => $year,
                'school_id' => $this->school->getKey(),
                'asn_certified' => 1,
                'asn_uncertified' => 0,
                'foundation_certified_inpassing' => 8,
                'foundation_uncertified' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $recap = EducatorRecap::query()->sole();

        $this->assertSame(9, $recap->total);
        $this->assertTrue($recap->submittedBy->is($this->admin));

        Livewire::test(EditEducatorRecap::class, ['record' => $recap->getRouteKey()])
            ->fillForm(['foundation_uncertified' => 2])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(11, $recap->fresh()->total);
    }

    public function test_year_selector_filters_educator_table_and_summary(): void
    {
        $currentYear = EducatorRecap::currentAcademicYear();
        $previousStart = ((int) explode('/', $currentYear)[0]) - 1;
        $previousYear = $previousStart.'/'.($previousStart + 1);

        $current = EducatorRecap::query()->create([
            'school_id' => $this->school->getKey(),
            'academic_year' => $currentYear,
            'asn_certified' => 5,
            'submitted_by' => $this->admin->getKey(),
        ]);
        $previous = EducatorRecap::query()->create([
            'school_id' => $this->school->getKey(),
            'academic_year' => $previousYear,
            'asn_certified' => 2,
            'submitted_by' => $this->admin->getKey(),
        ]);

        Livewire::test(ListEducatorRecaps::class)
            ->set('academicYear', $currentYear)
            ->assertCanSeeTableRecords([$current])
            ->assertCanNotSeeTableRecords([$previous])
            ->assertSee('5')
            ->assertSee('Madrasah Sudah Mengisi');
    }
}
