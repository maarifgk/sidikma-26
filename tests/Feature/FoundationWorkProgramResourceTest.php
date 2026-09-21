<?php

namespace Tests\Feature;

use App\Filament\Resources\FoundationWorkPrograms\FoundationWorkProgramResource;
use App\Filament\Resources\FoundationWorkPrograms\Pages\CreateFoundationWorkProgram;
use App\Filament\Resources\FoundationWorkPrograms\Pages\EditFoundationWorkProgram;
use App\Models\Foundation;
use App\Models\FoundationWorkProgram;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FoundationWorkProgramResourceTest extends TestCase
{
    use RefreshDatabase;

    private Foundation $foundation;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $this->foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_program_work_list_and_add_form(): void
    {
        $this->get(FoundationWorkProgramResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('PROGRAM KERJA')
            ->assertSee('Terlaksana')
            ->assertSee('Belum Terlaksana')
            ->assertSee('Tidak Terlaksana')
            ->assertSee('Program Kerja')
            ->assertSee('Tanggal Pelaksanaan')
            ->assertSee('Anggaran')
            ->assertSee('Keterangan')
            ->assertSee('Catatan')
            ->assertSee('Add');

        $this->get(FoundationWorkProgramResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tambah Program Kerja')
            ->assertSee('PROGRAM KERJA')
            ->assertSee('TANGGAL PELAKSANAAN')
            ->assertSee('ANGGARAN')
            ->assertSee('CATATAN');
    }

    public function test_admin_can_create_and_edit_program_work_for_application_foundation(): void
    {
        Livewire::test(CreateFoundationWorkProgram::class)
            ->fillForm([
                'name' => 'Rapat Koordinasi Madrasah',
                'implementation_date' => '2026-09-15',
                'budget' => 2500000,
                'status' => FoundationWorkProgram::STATUS_PLANNED,
                'notes' => 'Mengundang seluruh kepala madrasah.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $program = FoundationWorkProgram::query()->sole();

        $this->assertTrue($program->foundation->is($this->foundation));
        $this->assertSame('Rapat Koordinasi Madrasah', $program->name);

        Livewire::test(EditFoundationWorkProgram::class, ['record' => $program->getRouteKey()])
            ->fillForm([
                'status' => FoundationWorkProgram::STATUS_COMPLETED,
                'notes' => 'Kegiatan telah dilaksanakan.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(FoundationWorkProgram::STATUS_COMPLETED, $program->fresh()->status);
        $this->assertSame('Kegiatan telah dilaksanakan.', $program->fresh()->notes);
    }

    public function test_summary_displays_status_percentages(): void
    {
        foreach ([
            FoundationWorkProgram::STATUS_COMPLETED,
            FoundationWorkProgram::STATUS_COMPLETED,
            FoundationWorkProgram::STATUS_PLANNED,
            FoundationWorkProgram::STATUS_NOT_IMPLEMENTED,
        ] as $index => $status) {
            FoundationWorkProgram::query()->create([
                'foundation_id' => $this->foundation->getKey(),
                'name' => 'Program '.($index + 1),
                'status' => $status,
            ]);
        }

        $this->get(FoundationWorkProgramResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('50,0%')
            ->assertSee('25,0%');
    }
}
