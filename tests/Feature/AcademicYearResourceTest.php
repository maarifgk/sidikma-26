<?php

namespace Tests\Feature;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Filament\Resources\AcademicYears\Pages\CreateAcademicYear;
use App\Filament\Resources\AcademicYears\Pages\EditAcademicYear;
use App\Filament\Resources\AcademicYears\Pages\ListAcademicYears;
use App\Models\AcademicYear;
use App\Models\EducatorRecap;
use App\Models\Foundation;
use App\Models\StudentEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicYearResourceTest extends TestCase
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

        Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
    }

    public function test_academic_year_menu_displays_requested_table_and_actions(): void
    {
        $year = AcademicYear::query()->where('name', '2026/2027')->firstOrFail();

        $this->get(AcademicYearResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tahun Ajaran')
            ->assertSee('Add')
            ->assertSee('2026/2027')
            ->assertSee('2025/2026')
            ->assertSee('ON')
            ->assertSee('Dibuat')
            ->assertSee('Edit')
            ->assertSee('Delete');

        Livewire::test(ListAcademicYears::class)
            ->assertCanSeeTableRecords([$year])
            ->assertTableActionVisible(EditAction::class, $year)
            ->assertTableActionVisible(DeleteAction::class, $year);
    }

    public function test_admin_can_add_and_edit_academic_year(): void
    {
        Livewire::test(CreateAcademicYear::class)
            ->fillForm([
                'name' => '2027/2028',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $year = AcademicYear::query()->where('name', '2027/2028')->firstOrFail();

        $this->assertArrayHasKey('2027/2028', StudentEnrollment::academicYearOptions());
        $this->assertArrayHasKey('2027/2028', EducatorRecap::academicYearOptions());

        Livewire::test(EditAcademicYear::class, ['record' => $year->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($year->fresh()->is_active);
        $this->assertArrayNotHasKey('2027/2028', AcademicYear::activeOptions());
    }

    public function test_academic_year_must_use_consecutive_years(): void
    {
        Livewire::test(CreateAcademicYear::class)
            ->fillForm([
                'name' => '2027/2029',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);

        $this->assertDatabaseMissing('academic_years', ['name' => '2027/2029']);
    }
}
