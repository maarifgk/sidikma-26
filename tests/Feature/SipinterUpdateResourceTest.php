<?php

namespace Tests\Feature;

use App\Filament\Resources\SipinterUpdates\Pages\CreateSipinterUpdate;
use App\Filament\Resources\SipinterUpdates\Pages\EditSipinterUpdate;
use App\Filament\Resources\SipinterUpdates\Pages\ListSipinterUpdates;
use App\Filament\Resources\SipinterUpdates\SipinterUpdateResource;
use App\Models\Foundation;
use App\Models\School;
use App\Models\SipinterRequirement;
use App\Models\SipinterUpdate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SipinterUpdateResourceTest extends TestCase
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
            'name' => 'MI YAPPI Baleharjo',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_sipinter_update_list(): void
    {
        $this->assertSame(5, SipinterRequirement::query()->count());

        $this->get(SipinterUpdateResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Update Data Sipinter')
            ->assertSee('KELENGKAPAN UPDATE DATA SIPINTER')
            ->assertSee('Edit')
            ->assertSee('Input Data')
            ->assertSee('Asal Madrasah')
            ->assertSee('File Permohonan')
            ->assertSee('File Aset')
            ->assertSee('File Rekomendasi');

        $this->get(SipinterUpdateResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Input Data dan Dokumen')
            ->assertDontSee('Lengkapi Detail Data dan Dokumen')
            ->assertSee('Download PDF')
            ->assertSee('NPSN')
            ->assertSee('STATUS TANAH TEMPAT DIBANGUN SATUAN PENDIDIKAN TERSEBUT BERUPA TANAH')
            ->assertSee('UPLOAD FILE SURAT PERMOHONAN (PDF)');
    }

    public function test_admin_can_edit_sipinter_requirements(): void
    {
        Storage::fake(SipinterUpdate::DISK);

        Livewire::test(ListSipinterUpdates::class)
            ->callAction('editRequirements', data: [
                'items' => [
                    [
                        'id' => null,
                        'number' => '1.',
                        'description' => 'Persyaratan Sipinter terbaru.',
                        'template_label' => 'Download Template',
                        'template_path' => [
                            UploadedFile::fake()->create('template-sipinter.pdf', 100, 'application/pdf'),
                        ],
                        'is_active' => true,
                    ],
                    [
                        'id' => null,
                        'number' => 'KET:',
                        'description' => 'Keterangan Sipinter terbaru.',
                        'is_active' => true,
                    ],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['Persyaratan Sipinter terbaru.', 'Keterangan Sipinter terbaru.'],
            SipinterRequirement::query()->ordered()->pluck('description')->all(),
        );
        $this->assertSame([1, 2], SipinterRequirement::query()->ordered()->pluck('position')->all());

        $requirement = SipinterRequirement::query()->ordered()->firstOrFail();
        $this->assertSame('Download Template', $requirement->template_label);
        Storage::disk(SipinterUpdate::DISK)->assertExists($requirement->template_path);
    }

    public function test_admin_can_input_sipinter_files_for_a_school(): void
    {
        Storage::fake(SipinterUpdate::DISK);

        Livewire::test(CreateSipinterUpdate::class)
            ->fillForm([
                'school_id' => $this->school->getKey(),
                'npsn' => '12345678',
                'school_address' => 'Jalan Pendidikan Nomor 1',
                'land_ownership' => 'milik_yayasan',
                'land_status' => 'sertifikat_hak_milik',
                'management_authority' => 'lp_maarif_pcnu',
                'uses_notarial_deed' => true,
                'request_file_path' => UploadedFile::fake()->create('permohonan.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $update = SipinterUpdate::query()->sole();

        $this->assertTrue($update->school->is($this->school));
        $this->assertTrue($update->uploadedBy->is($this->admin));
        $this->assertSame('12345678', $update->npsn);
        $this->assertSame('milik_yayasan', $update->land_ownership);
        $this->assertTrue($update->uses_notarial_deed);
        $this->assertNull($update->asset_file_path);
        $this->assertNull($update->recommendation_file_path);
        Storage::disk(SipinterUpdate::DISK)->assertExists($update->request_file_path);
    }

    public function test_admin_can_download_request_letter_template_as_pdf(): void
    {
        $response = $this->get(route('sipinter.template.download'));

        $response
            ->assertOk()
            ->assertDownload('template-surat-permohonan-sipinter.pdf')
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
    }

    public function test_updating_and_deleting_record_cleans_replaced_files(): void
    {
        Storage::fake(SipinterUpdate::DISK);
        Storage::disk(SipinterUpdate::DISK)->put('request-files/old.pdf', 'old request');
        Storage::disk(SipinterUpdate::DISK)->put('asset-files/asset.pdf', 'asset');
        Storage::disk(SipinterUpdate::DISK)->put('recommendation-files/recommendation.pdf', 'recommendation');

        $update = SipinterUpdate::query()->create([
            'school_id' => $this->school->getKey(),
            'request_file_path' => 'request-files/old.pdf',
            'asset_file_path' => 'asset-files/asset.pdf',
            'recommendation_file_path' => 'recommendation-files/recommendation.pdf',
            'uploaded_by' => $this->admin->getKey(),
        ]);

        Livewire::test(EditSipinterUpdate::class, ['record' => $update->getRouteKey()])
            ->assertFormSet([
                'school_id' => $this->school->getKey(),
                'request_file_path' => 'request-files/old.pdf',
            ]);

        Storage::disk(SipinterUpdate::DISK)->put('request-files/new.pdf', 'new request');
        $update->update(['request_file_path' => 'request-files/new.pdf']);

        $update->refresh();
        Storage::disk(SipinterUpdate::DISK)->assertMissing('request-files/old.pdf');
        Storage::disk(SipinterUpdate::DISK)->assertExists($update->request_file_path);

        $paths = collect(SipinterUpdate::FILE_FIELDS)
            ->map(fn (string $field): string => $update->getAttribute($field));

        $update->delete();

        $this->assertDatabaseMissing('sipinter_updates', ['id' => $update->getKey()]);
        $paths->each(fn (string $path) => Storage::disk(SipinterUpdate::DISK)->assertMissing($path));
    }
}
