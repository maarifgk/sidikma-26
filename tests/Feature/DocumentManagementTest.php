<?php

namespace Tests\Feature;

use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Foundations\FoundationResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($this->admin);
    }

    public function test_document_manager_is_available_for_all_supported_owners(): void
    {
        $this->assertContains(DocumentsRelationManager::class, EmployeeResource::getRelations());
        $this->assertContains(DocumentsRelationManager::class, FoundationResource::getRelations());
        $this->assertContains(DocumentsRelationManager::class, SchoolResource::getRelations());
    }

    public function test_admin_can_upload_private_document_from_employee_page(): void
    {
        Storage::fake(Document::PRIVATE_DISK);
        $employee = Employee::factory()->create();
        $file = UploadedFile::fake()->createWithContent(
            'surat-keputusan.pdf',
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n",
        );

        $this->relationManager($employee)
            ->callTableAction(CreateAction::class, data: [
                'document_type' => 'sk',
                'path' => $file,
            ])
            ->assertHasNoFormErrors();

        $document = $employee->documents()->sole();

        $this->assertSame('sk', $document->document_type);
        $this->assertSame(Document::PRIVATE_DISK, $document->disk);
        $this->assertSame('surat-keputusan.pdf', $document->original_name);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertSame($this->admin->getKey(), $document->uploaded_by);
        $this->assertSame(Document::STATUS_ACTIVE, $document->status);
        $this->assertStringStartsWith("employees/{$employee->getKey()}/", $document->path);
        $this->assertSame(64, strlen($document->checksum));
        Storage::disk(Document::PRIVATE_DISK)->assertExists($document->path);

        $this->relationManager($employee)
            ->assertCanSeeTableRecords([$document])
            ->assertSee('surat-keputusan.pdf');
    }

    public function test_upload_rejects_disallowed_file_type(): void
    {
        Storage::fake(Document::PRIVATE_DISK);
        $employee = Employee::factory()->create();
        $file = UploadedFile::fake()->create('program.exe', 10, 'application/x-msdownload');

        $this->relationManager($employee)
            ->callTableAction(CreateAction::class, data: [
                'document_type' => 'lainnya',
                'path' => $file,
            ])
            ->assertHasFormErrors(['path']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_rejects_file_larger_than_one_thousand_megabytes(): void
    {
        Storage::fake(Document::PRIVATE_DISK);
        $employee = Employee::factory()->create();
        $file = UploadedFile::fake()->create('terlalu-besar.pdf', 1_024_001, 'application/pdf');

        $this->relationManager($employee)
            ->callTableAction(CreateAction::class, data: [
                'document_type' => 'sk',
                'path' => $file,
            ])
            ->assertHasFormErrors(['path']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_authorized_admin_can_download_private_document(): void
    {
        Storage::fake(Document::PRIVATE_DISK);
        $document = Document::factory()->create([
            'uploaded_by' => $this->admin->getKey(),
            'original_name' => 'surat-keputusan.pdf',
            'mime_type' => 'application/pdf',
        ]);
        Storage::disk(Document::PRIVATE_DISK)->put($document->path, 'isi dokumen rahasia');

        $response = $this->get(route('documents.download', $document));

        $response
            ->assertOk()
            ->assertDownload('surat-keputusan.pdf')
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');
        $this->assertSame('isi dokumen rahasia', $response->streamedContent());
    }

    public function test_unauthenticated_and_unauthorized_users_cannot_download_document(): void
    {
        Storage::fake(Document::PRIVATE_DISK);
        $document = Document::factory()->create(['uploaded_by' => $this->admin->getKey()]);
        Storage::disk(Document::PRIVATE_DISK)->put($document->path, 'rahasia');

        auth()->logout();
        $this->get(route('documents.download', $document))->assertRedirect();

        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        $this->actingAs($schoolAdmin)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    public function test_download_returns_not_found_when_private_file_is_missing(): void
    {
        Storage::fake(Document::PRIVATE_DISK);
        $document = Document::factory()->create(['uploaded_by' => $this->admin->getKey()]);

        $this->get(route('documents.download', $document))->assertNotFound();
    }

    private function relationManager(Employee $employee): mixed
    {
        return Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ]);
    }
}
