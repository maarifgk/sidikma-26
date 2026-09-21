<?php

namespace Tests\Feature;

use App\Filament\Resources\CorrespondenceRequests\CorrespondenceRequestResource;
use App\Filament\Resources\CorrespondenceRequests\Pages\ListCorrespondenceRequests;
use App\Filament\Resources\DecreeProposals\DecreeProposalResource;
use App\Filament\Resources\DecreeProposals\Pages\ListDecreeProposals;
use App\Filament\Resources\EmployeeActivityRequests\EmployeeActivityRequestResource;
use App\Filament\Resources\EmployeeActivityRequests\Pages\ListEmployeeActivityRequests;
use App\Filament\Resources\EmployeeMutations\EmployeeMutationResource;
use App\Filament\Resources\EmployeeMutations\Pages\ListEmployeeMutations;
use App\Filament\Resources\ProposalRequests\Pages\ListProposalRequests;
use App\Filament\Resources\ProposalRequests\ProposalRequestResource;
use App\Filament\Resources\SipinterUpdates\Pages\ListSipinterUpdates;
use App\Filament\Resources\SipinterUpdates\SipinterUpdateResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdministrationRequirementEditorsTest extends TestCase
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

    public function test_all_administration_requirement_editors_use_save_changes_button(): void
    {
        $editors = [
            [ListDecreeProposals::class, 'editRequirements'],
            [ListSipinterUpdates::class, 'editRequirements'],
            [ListEmployeeMutations::class, 'editRequirements'],
            [ListEmployeeActivityRequests::class, 'editRequirements'],
            [ListCorrespondenceRequests::class, 'editTypes'],
            [ListProposalRequests::class, 'editRequirements'],
        ];

        foreach ($editors as [$component, $action]) {
            Livewire::test($component)
                ->assertActionExists(
                    $action,
                    fn (Action $filamentAction): bool => $filamentAction->getModalSubmitActionLabel() === 'Simpan Perubahan',
                );
        }

        $pages = [
            [DecreeProposalResource::getUrl(panel: 'admin', isAbsolute: false), 'editRequirements'],
            [SipinterUpdateResource::getUrl(panel: 'admin', isAbsolute: false), 'editRequirements'],
            [EmployeeMutationResource::getUrl(panel: 'admin', isAbsolute: false), 'editRequirements'],
            [EmployeeActivityRequestResource::getUrl(panel: 'admin', isAbsolute: false), 'editRequirements'],
            [CorrespondenceRequestResource::getUrl(panel: 'admin', isAbsolute: false), 'editTypes'],
            [ProposalRequestResource::getUrl(panel: 'admin', isAbsolute: false), 'editRequirements'],
        ];

        foreach ($pages as [$url, $action]) {
            $this->get($url)
                ->assertOk()
                ->assertSee("wire:click=\"mountAction('{$action}')\"", false);
        }
    }

    public function test_administration_pages_only_show_page_title_without_subheading(): void
    {
        $pages = [
            [ListDecreeProposals::class, 'Kelola pengajuan guru/pegawai baru dan pantau status proses dalam satu halaman.'],
            [ListSipinterUpdates::class, "Detail pembaruan data SIPINTER Ma'arif, dokumen pendukung, dan rekap data satuan pendidikan."],
            [ListEmployeeMutations::class, 'Pantau permohonan mutasi guru dan pegawai beserta dokumen pendukung dalam satu tampilan.'],
            [ListCorrespondenceRequests::class, 'Lihat file masuk, file balasan, dan status proses pengajuan persuratan.'],
            [ListProposalRequests::class, 'Kelola pengajuan proposal, nominal bantuan, dan file persetujuan dengan tampilan yang lebih bersih.'],
        ];

        foreach ($pages as [$component, $subheading]) {
            Livewire::test($component)->assertDontSee($subheading);
        }
    }
}
