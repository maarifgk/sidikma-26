<?php

namespace Tests\Feature;

use App\Filament\Resources\SecretariatAgendas\Pages\CreateSecretariatAgenda;
use App\Filament\Resources\SecretariatAgendas\SecretariatAgendaResource;
use App\Models\Foundation;
use App\Models\SecretariatAgenda;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecretariatAgendaResourceTest extends TestCase
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

    public function test_admin_can_open_agenda_list_and_add_form(): void
    {
        $this->get(SecretariatAgendaResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Agenda Kesekretariatan')
            ->assertSee('Add')
            ->assertSee('Kegiatan')
            ->assertSee('Tanggal Pelaksanaan')
            ->assertSee('Petugas')
            ->assertSee('Keterangan')
            ->assertSee('Catatan');

        $this->get(SecretariatAgendaResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Tambah Agenda Kesekretariatan')
            ->assertSee('KEGIATAN')
            ->assertSee('TANGGAL PELAKSANAAN')
            ->assertSee('PETUGAS')
            ->assertSee('KETERANGAN')
            ->assertSee('CATATAN');
    }

    public function test_admin_can_create_agenda_for_application_foundation(): void
    {
        Livewire::test(CreateSecretariatAgenda::class)
            ->fillForm([
                'activity' => "Rapat Sosialisasi Kepala MI terkait Program Pendampingan MI Unggulan Ma'arif Gunungkidul",
                'implementation_date' => '2026-02-12',
                'officer' => "KKMI Ma'arif",
                'status' => SecretariatAgenda::STATUS_COMPLETED,
                'notes' => "Snack disiapkan LP. Ma'arif 55 dus",
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $agenda = SecretariatAgenda::query()->sole();

        $this->assertTrue($agenda->foundation->is($this->foundation));
        $this->assertSame(SecretariatAgenda::STATUS_COMPLETED, $agenda->status);

        $this->get(SecretariatAgendaResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('12 Februari 2026')
            ->assertSee("KKMI Ma'arif")
            ->assertSee('Terlaksana')
            ->assertSee('Delete');
    }
}
