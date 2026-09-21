<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('documents', [
            'id',
            'document_type',
            'owner_type',
            'owner_id',
            'disk',
            'path',
            'original_name',
            'mime_type',
            'size',
            'checksum',
            'status',
            'uploaded_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));
    }

    public function test_document_disk_is_private_and_has_no_public_url_configuration(): void
    {
        $disk = config('filesystems.disks.documents');

        $this->assertSame('local', $disk['driver']);
        $this->assertSame(storage_path('app/private/documents'), $disk['root']);
        $this->assertFalse($disk['serve']);
        $this->assertSame('private', $disk['visibility']);
        $this->assertArrayNotHasKey('url', $disk);
        $this->assertNotContains(
            storage_path('app/private/documents'),
            config('filesystems.links'),
        );
    }

    public function test_documents_can_belong_to_employee_foundation_and_school(): void
    {
        $uploader = User::factory()->create();
        $employee = Employee::factory()->create();
        $foundation = Foundation::factory()->create();
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'Madrasah Dokumen',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $employeeDocument = Document::factory()->create([
            'owner_type' => Employee::class,
            'owner_id' => $employee->getKey(),
            'uploaded_by' => $uploader->getKey(),
        ]);
        $foundationDocument = Document::factory()->create([
            'owner_type' => Foundation::class,
            'owner_id' => $foundation->getKey(),
        ]);
        $schoolDocument = Document::factory()->create([
            'owner_type' => School::class,
            'owner_id' => $school->getKey(),
        ]);

        $this->assertTrue($employeeDocument->owner->is($employee));
        $this->assertTrue($foundationDocument->owner->is($foundation));
        $this->assertTrue($schoolDocument->owner->is($school));
        $this->assertTrue($employeeDocument->uploadedBy->is($uploader));
        $this->assertTrue($employee->documents->contains($employeeDocument));
        $this->assertTrue($foundation->documents->contains($foundationDocument));
        $this->assertTrue($school->documents->contains($schoolDocument));
        $this->assertTrue($uploader->uploadedDocuments->contains($employeeDocument));
    }

    public function test_document_rejects_public_disk_and_unsafe_path(): void
    {
        try {
            Document::factory()->create(['disk' => 'public']);

            $this->fail('Dokumen pada disk publik seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Dokumen wajib disimpan pada penyimpanan privat.',
                $exception->errors()['disk'][0],
            );
        }

        try {
            Document::factory()->create(['path' => '../rahasia.pdf']);

            $this->fail('Path traversal seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Lokasi dokumen tidak valid.',
                $exception->errors()['path'][0],
            );
        }

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_document_path_must_be_unique(): void
    {
        Document::factory()->create(['path' => 'employees/dokumen-sama.pdf']);

        $this->expectException(QueryException::class);

        Document::factory()->create(['path' => 'employees/dokumen-sama.pdf']);
    }

    public function test_soft_delete_keeps_private_file_for_restore(): void
    {
        Storage::fake(Document::PRIVATE_DISK);
        $document = Document::factory()->create();
        Storage::disk(Document::PRIVATE_DISK)->put($document->path, 'isi dokumen rahasia');

        $document->delete();

        $this->assertSoftDeleted('documents', ['id' => $document->getKey()]);
        $this->assertNotNull(Document::withTrashed()->find($document->getKey()));
        Storage::disk(Document::PRIVATE_DISK)->assertExists($document->path);
    }

    public function test_document_policy_requires_admin_induk_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $document = Document::factory()->create(['uploaded_by' => $admin->getKey()]);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $document));
        $this->assertTrue(Gate::forUser($admin)->allows('download', $document));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Document::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $document));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $document));
        $this->assertFalse(Gate::forUser($admin)->allows('forceDelete', $document));
        $this->assertFalse(Gate::forUser($schoolAdmin)->allows('view', $document));
        $this->assertFalse(Gate::forUser($schoolAdmin)->allows('download', $document));
    }
}
