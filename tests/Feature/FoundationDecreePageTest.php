<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\FoundationDecrees;
use App\Models\DecreeTemplate;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FoundationDecreePageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

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
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
        $this->school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Baleharjo',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $this->teacher = User::factory()->create([
            'name' => 'Guru Penerima SK',
            'email' => 'guru-sk@example.test',
        ]);
        $this->teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        Membership::query()->create([
            'user_id' => $this->teacher->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'status' => 'active',
        ]);
        Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'user_id' => $this->teacher->getKey(),
            'employee_code' => 'EWANUGK-SK-001',
            'name' => $this->teacher->name,
        ]);
    }

    public function test_admin_can_search_user_without_initial_long_dropdown(): void
    {
        Livewire::test(FoundationDecrees::class)
            ->assertSee('Upload Surat Keputusan')
            ->assertSee('Ketik minimal 2 karakter')
            ->assertDontSee($this->teacher->email)
            ->set('userSearch', 'EWANUGK-SK')
            ->assertSee($this->teacher->name)
            ->assertSee($this->teacher->email)
            ->call('selectUser', $this->teacher->getKey())
            ->assertSet('selectedUserId', $this->teacher->getKey());
    }

    public function test_uploaded_decree_is_private_linked_and_searchable(): void
    {
        Storage::fake(Document::PRIVATE_DISK);

        Livewire::test(FoundationDecrees::class)
            ->set('selectedUserId', $this->teacher->getKey())
            ->set('decreeKind', 'SK Pengangkatan')
            ->set('decreeNumber', '123/LP-MAARIF/VIII/2026')
            ->set('decreeDate', '2026-08-27')
            ->set('decreeNotes', 'Dokumen pengangkatan guru')
            ->set('decreeFile', UploadedFile::fake()->create(
                'SK-Guru-Penerima-2026.pdf',
                256,
                'application/pdf',
            ))
            ->call('uploadDecree')
            ->assertHasNoErrors();

        $document = Document::query()->sole();

        $this->assertSame('foundation_decree:2026', $document->document_type);
        $this->assertSame(User::class, $document->owner_type);
        $this->assertSame($this->teacher->getKey(), $document->owner_id);
        $this->assertSame(Document::PRIVATE_DISK, $document->disk);
        $this->assertSame('SK Pengangkatan', $document->decree_kind);
        $this->assertSame('123/LP-MAARIF/VIII/2026', $document->decree_number);
        Storage::disk(Document::PRIVATE_DISK)->assertExists($document->path);

        Livewire::test(FoundationDecrees::class)
            ->assertSee('Daftar Surat Keputusan')
            ->assertSee('SK-Guru-Penerima-2026.pdf')
            ->assertSee($this->school->name)
            ->set('documentSearch', 'Baleharjo')
            ->assertSee('SK-Guru-Penerima-2026.pdf')
            ->set('documentSearch', 'dokumen-tidak-ada')
            ->assertDontSee('SK-Guru-Penerima-2026.pdf')
            ->assertSee('Belum Ada Surat Keputusan');
    }

    public function test_teacher_can_only_download_own_private_decree(): void
    {
        Storage::fake(Document::PRIVATE_DISK);
        Storage::disk(Document::PRIVATE_DISK)->put('foundation-decrees/2026/own.pdf', 'own decree');
        Storage::disk(Document::PRIVATE_DISK)->put('foundation-decrees/2026/other.pdf', 'other decree');

        $own = Document::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $this->teacher->getKey(),
            'document_type' => 'foundation_decree:2026',
            'disk' => Document::PRIVATE_DISK,
            'path' => 'foundation-decrees/2026/own.pdf',
            'original_name' => 'SK-Saya.pdf',
        ]);
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $other = Document::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $otherTeacher->getKey(),
            'document_type' => 'foundation_decree:2026',
            'disk' => Document::PRIVATE_DISK,
            'path' => 'foundation-decrees/2026/other.pdf',
            'original_name' => 'SK-Orang-Lain.pdf',
        ]);

        $this->actingAs($this->teacher)
            ->get(route('documents.download', $own))
            ->assertOk();
        $this->get(route('documents.download', $other))->assertForbidden();
    }

    public function test_admin_can_add_and_edit_a_decree_template(): void
    {
        Storage::fake(Document::PRIVATE_DISK);

        Livewire::test(FoundationDecrees::class)
            ->call('openCreateTemplate')
            ->set('templateName', 'SK Perpanjangan Tahun 2026')
            ->set('templatePaperSize', 'F4')
            ->set('templateOrientation', 'landscape')
            ->set('decreeTemplateFile', UploadedFile::fake()->create(
                'template-sk-2026.docx',
                32,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ))
            ->call('saveTemplate')
            ->assertHasNoErrors()
            ->assertSee('SK Perpanjangan Tahun 2026');

        $template = DecreeTemplate::query()->sole();

        $this->assertSame('F4', $template->paper_size);
        $this->assertSame('landscape', $template->orientation);
        $this->assertTrue($template->is_active);
        Storage::disk(Document::PRIVATE_DISK)->assertExists($template->path);

        Livewire::test(FoundationDecrees::class)
            ->call('openEditTemplate', $template->getKey())
            ->set('templateName', 'SK Perpanjangan Revisi')
            ->set('templateIsActive', false)
            ->call('saveTemplate')
            ->assertHasNoErrors()
            ->assertSee('SK Perpanjangan Revisi')
            ->assertSee('NONAKTIF');
    }

    public function test_bulk_import_matches_employee_code_and_skips_duplicate(): void
    {
        Storage::fake(Document::PRIVATE_DISK);

        $component = Livewire::test(FoundationDecrees::class)
            ->set('bulkYear', '2026')
            ->set('bulkSchoolId', $this->school->getKey())
            ->set('bulkFiles', [
                UploadedFile::fake()->create('SK-EWANUGK-SK-001-2026.pdf', 32, 'application/pdf'),
                UploadedFile::fake()->create('SK-user-tidak-dikenal-2026.pdf', 32, 'application/pdf'),
            ])
            ->call('importBulkDecrees')
            ->assertHasNoErrors()
            ->assertSet('bulkImportSummary.imported', 1)
            ->assertSet('bulkImportSummary.skipped', 0)
            ->assertSee('SK-EWANUGK-SK-001-2026.pdf')
            ->assertSee('SK-user-tidak-dikenal-2026.pdf');

        $document = Document::query()->sole();

        $this->assertSame($this->teacher->getKey(), $document->owner_id);
        $this->assertSame('foundation_decree:2026', $document->document_type);
        Storage::disk(Document::PRIVATE_DISK)->assertExists($document->path);

        $component
            ->set('bulkFiles', [
                UploadedFile::fake()->create('SK-EWANUGK-SK-001-2026-copy.pdf', 32, 'application/pdf'),
            ])
            ->call('importBulkDecrees')
            ->assertHasNoErrors()
            ->assertSet('bulkImportSummary.imported', 0)
            ->assertSet('bulkImportSummary.skipped', 1);

        $this->assertSame(1, Document::query()->count());
    }

    public function test_school_admin_cannot_open_parent_decree_page(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        $this->actingAs($schoolAdmin)
            ->get(FoundationDecrees::getUrl(panel: 'admin', isAbsolute: false))
            ->assertForbidden();
    }
}
