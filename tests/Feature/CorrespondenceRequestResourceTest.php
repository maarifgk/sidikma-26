<?php

namespace Tests\Feature;

use App\Filament\Resources\CorrespondenceRequests\CorrespondenceRequestResource;
use App\Filament\Resources\CorrespondenceRequests\Pages\CreateCorrespondenceRequest;
use App\Filament\Resources\CorrespondenceRequests\Pages\ListCorrespondenceRequests;
use App\Models\CorrespondenceRequest;
use App\Models\CorrespondenceType;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CorrespondenceRequestResourceTest extends TestCase
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
            'name' => 'MI YAPPI Banjaran',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_correspondence_pages_with_default_types(): void
    {
        $this->assertSame(5, CorrespondenceType::query()->count());

        $this->get(CorrespondenceRequestResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('PERMOHONAN PERSURATAN')
            ->assertSee('JENIS-JENIS PERMOHONAN PERSURATAN')
            ->assertSee('Surat Pernyataan')
            ->assertSee('Surat Rekomendasi')
            ->assertSee('Surat Perintah Tugas')
            ->assertSee('Surat Keterangan')
            ->assertSee('Ajukan')
            ->assertSee('Edit');

        $this->get(CorrespondenceRequestResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Input Data Permohonan Persuratan')
            ->assertSee('ASAL MADRASAH/SEKOLAH')
            ->assertSee('JENIS PERMOHONAN PERSURATAN')
            ->assertSee('UPLOAD FILE PERMOHONAN (PDF)');
    }

    public function test_admin_can_submit_and_delete_correspondence_request(): void
    {
        Storage::fake(CorrespondenceRequest::DISK);
        $type = CorrespondenceType::query()->where('is_selectable', true)->ordered()->firstOrFail();

        Livewire::test(CreateCorrespondenceRequest::class)
            ->fillForm([
                'school_id' => $this->school->getKey(),
                'correspondence_type_id' => $type->getKey(),
                'request_file_path' => UploadedFile::fake()->create('surat-permohonan.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $request = CorrespondenceRequest::query()->sole();

        $this->assertSame($this->school->name, $request->school_name);
        $this->assertSame($type->name, $request->type_name);
        $this->assertSame(CorrespondenceRequest::STATUS_SUBMITTED, $request->process_status);
        $this->assertTrue($request->submittedBy->is($this->admin));
        Storage::disk(CorrespondenceRequest::DISK)->assertExists($request->request_file_path);

        $path = $request->request_file_path;
        $request->delete();

        Storage::disk(CorrespondenceRequest::DISK)->assertMissing($path);
    }

    public function test_admin_can_edit_correspondence_types_and_notes(): void
    {
        Livewire::test(ListCorrespondenceRequests::class)
            ->callAction('editTypes', data: [
                'items' => [
                    [
                        'id' => null,
                        'number' => '1.',
                        'name' => 'Surat Rekomendasi Baru',
                        'is_selectable' => true,
                        'is_active' => true,
                    ],
                    [
                        'id' => null,
                        'number' => 'KET:',
                        'name' => 'Keterangan persuratan terbaru.',
                        'is_selectable' => false,
                        'is_active' => true,
                    ],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['Surat Rekomendasi Baru', 'Keterangan persuratan terbaru.'],
            CorrespondenceType::query()->ordered()->pluck('name')->all(),
        );
        $this->assertSame([1, 2], CorrespondenceType::query()->ordered()->pluck('position')->all());
        $this->assertSame([true, false], CorrespondenceType::query()->ordered()->pluck('is_selectable')->all());
    }
}
