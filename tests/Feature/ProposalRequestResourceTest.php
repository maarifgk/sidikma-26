<?php

namespace Tests\Feature;

use App\Filament\Resources\ProposalRequests\Pages\CreateProposalRequest;
use App\Filament\Resources\ProposalRequests\Pages\ListProposalRequests;
use App\Filament\Resources\ProposalRequests\ProposalRequestResource;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\ProposalRequest;
use App\Models\ProposalRequirement;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProposalRequestResourceTest extends TestCase
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
            'name' => 'MI YAPPI Mulusan',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_proposal_pages_with_default_requirements(): void
    {
        $this->assertSame(5, ProposalRequirement::query()->count());

        $this->get(ProposalRequestResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('PENGAJUAN PROPOSAL')
            ->assertSee('DATA PENGAJUAN PROPOSAL')
            ->assertSee('Dokumen Surat Permohonan Bantuan/Proposal dalam bentuk File PDF')
            ->assertSee('Nominal yang diajukan')
            ->assertSee('Nama Bank Dan Nomor Rekening')
            ->assertSee('Ajukan')
            ->assertSee('Edit');

        $this->get(ProposalRequestResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Ajukan Permohonan Bantuan/Proposal')
            ->assertSee('NAMA MADRASAH/SEKOLAH')
            ->assertSee('JENIS PERMOHONAN BANTUAN/PROPOSAL')
            ->assertSee('Permohonan Bantuan untuk Pembangunan')
            ->assertSee('Permohonan Bantuan untuk Kegiatan')
            ->assertSee('NOMINAL YANG DIAJUKAN')
            ->assertSee('UPLOAD SURAT PERMOHONAN BANTUAN/PROPOSAL (PDF)')
            ->assertSee('ATAS NAMA REKENING');
    }

    public function test_admin_can_submit_and_process_proposal(): void
    {
        Storage::fake(ProposalRequest::DISK);

        Livewire::test(CreateProposalRequest::class)
            ->fillForm([
                'school_id' => $this->school->getKey(),
                'proposal_type' => 'Permohonan Bantuan untuk Pembangunan',
                'requested_amount' => 4290000,
                'bank_name' => 'Bank Syariah Indonesia',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'MI YAPPI Mulusan',
                'request_file_path' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(ProposalRequestResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertNotified('Data berhasil disimpan');

        $request = ProposalRequest::query()->sole();

        $this->assertSame($this->school->name, $request->school_name);
        $this->assertSame('MI YAPPI Mulusan', $request->bank_account_name);
        $this->assertSame(ProposalRequest::STATUS_SUBMITTED, $request->process_status);
        Storage::disk(ProposalRequest::DISK)->assertExists($request->request_file_path);

        Livewire::test(ListProposalRequests::class)
            ->callTableAction('process', $request, data: [
                'approval_file_path' => UploadedFile::fake()->create('persetujuan.pdf', 100, 'application/pdf'),
                'approved_amount' => 4000000,
                'notes' => 'Bantuan disetujui sebagian.',
            ])
            ->assertHasNoTableActionErrors();

        $request->refresh();

        $this->assertSame(ProposalRequest::STATUS_COMPLETED, $request->process_status);
        $this->assertSame('4000000.00', $request->approved_amount);
        $this->assertSame('Bantuan disetujui sebagian.', $request->notes);
        $this->assertTrue($request->processedBy->is($this->admin));
        Storage::disk(ProposalRequest::DISK)->assertExists($request->approval_file_path);

        $paths = [$request->request_file_path, $request->approval_file_path];
        $request->delete();

        foreach ($paths as $path) {
            Storage::disk(ProposalRequest::DISK)->assertMissing($path);
        }
    }

    public function test_admin_can_reject_proposal_with_notes(): void
    {
        Storage::fake(ProposalRequest::DISK);
        Storage::disk(ProposalRequest::DISK)->put('request-files/proposal.pdf', 'pdf');

        $request = ProposalRequest::query()->create([
            'school_id' => $this->school->getKey(),
            'school_name' => $this->school->name,
            'proposal_type' => 'Permohonan Bantuan',
            'request_file_path' => 'request-files/proposal.pdf',
            'requested_amount' => 1000000,
            'bank_name' => 'BSI',
            'bank_account_number' => '123456',
            'process_status' => ProposalRequest::STATUS_SUBMITTED,
            'submitted_by' => $this->admin->getKey(),
        ]);

        Livewire::test(ListProposalRequests::class)
            ->callTableAction('reject', $request, data: [
                'notes' => 'Dokumen proposal belum lengkap.',
            ])
            ->assertHasNoTableActionErrors();

        $request->refresh();

        $this->assertSame(ProposalRequest::STATUS_REJECTED, $request->process_status);
        $this->assertSame('Dokumen proposal belum lengkap.', $request->notes);
        $this->assertTrue($request->processedBy->is($this->admin));
    }

    public function test_school_admin_cannot_process_or_reject_proposal(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $this->school->foundation_id,
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);
        $request = ProposalRequest::query()->create([
            'school_id' => $this->school->getKey(),
            'school_name' => $this->school->name,
            'proposal_type' => 'Permohonan Bantuan',
            'request_file_path' => 'request-files/proposal.pdf',
            'requested_amount' => 1000000,
            'bank_name' => 'BSI',
            'bank_account_number' => '123456',
            'process_status' => ProposalRequest::STATUS_SUBMITTED,
            'submitted_by' => $schoolAdmin->getKey(),
        ]);

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($schoolAdmin);

        Livewire::test(ListProposalRequests::class)
            ->assertTableActionHidden('process', $request)
            ->assertTableActionHidden('reject', $request);

        $this->assertFalse($schoolAdmin->can('review', $request));
        $this->assertSame(ProposalRequest::STATUS_SUBMITTED, $request->fresh()->process_status);
    }

    public function test_admin_can_edit_proposal_requirements(): void
    {
        Livewire::test(ListProposalRequests::class)
            ->callAction('editRequirements', data: [
                'items' => [
                    [
                        'id' => null,
                        'number' => '1.',
                        'description' => 'Dokumen proposal terbaru.',
                        'is_active' => true,
                    ],
                    [
                        'id' => null,
                        'number' => 'NB:',
                        'description' => 'Hubungi admin jika mengalami kendala.',
                        'is_active' => true,
                    ],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['Dokumen proposal terbaru.', 'Hubungi admin jika mengalami kendala.'],
            ProposalRequirement::query()->ordered()->pluck('description')->all(),
        );
        $this->assertSame([1, 2], ProposalRequirement::query()->ordered()->pluck('position')->all());
    }

    public function test_invalid_administration_form_shows_red_failure_notification(): void
    {
        Livewire::test(CreateProposalRequest::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors()
            ->assertNotified('Gagal mengubah data');
    }
}
