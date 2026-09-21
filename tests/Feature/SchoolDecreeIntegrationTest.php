<?php

namespace Tests\Feature;

use App\Filament\App\Pages\SchoolDecrees;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolDecreeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $parentAdmin;

    private User $schoolAdmin;

    private User $teacher;

    private User $otherTeacher;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake(Document::PRIVATE_DISK);

        $foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
        $this->school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI Sekolah Admin',
            'npsn' => '10000001',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $otherSchool = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MTs Sekolah Lain',
            'npsn' => '10000002',
            'school_level' => 'MTs',
            'is_active' => true,
        ]);

        $this->parentAdmin = User::factory()->create();
        $this->parentAdmin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->schoolAdmin = User::factory()->create();
        $this->schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->teacher = User::factory()->create(['name' => 'Guru Sekolah Sendiri']);
        $this->teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->otherTeacher = User::factory()->create(['name' => 'Guru Sekolah Lain']);
        $this->otherTeacher->assignRole(User::ROLE_GURU_PEGAWAI);

        foreach ([
            [$this->schoolAdmin, $this->school],
            [$this->teacher, $this->school],
            [$this->otherTeacher, $otherSchool],
        ] as [$user, $school]) {
            Membership::query()->create([
                'user_id' => $user->getKey(),
                'foundation_id' => $foundation->getKey(),
                'school_id' => $school->getKey(),
                'status' => 'active',
            ]);
        }

        Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'user_id' => $this->teacher->getKey(),
            'employee_code' => 'EWANUGK-ADMIN-001',
            'name' => $this->teacher->name,
        ]);
        Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $otherSchool->getKey(),
            'user_id' => $this->otherTeacher->getKey(),
            'employee_code' => 'EWANUGK-LAIN-001',
            'name' => $this->otherTeacher->name,
        ]);
    }

    public function test_school_admin_sees_only_decrees_imported_for_accessible_school(): void
    {
        $ownDocument = $this->createDecree($this->teacher, 'sk-guru-sendiri-2026.pdf', 'school-decrees/sendiri.pdf');
        $this->createDecree($this->otherTeacher, 'sk-guru-lain-2026.pdf', 'school-decrees/lain.pdf');

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($this->schoolAdmin);

        Livewire::test(SchoolDecrees::class)
            ->assertSee('SK Yayasan')
            ->assertDontSee('Semua madrasah yang dapat diakses')
            ->assertSee($this->teacher->name)
            ->assertSee('EWANUGK-ADMIN-001')
            ->assertSee($ownDocument->original_name)
            ->assertDontSee($this->otherTeacher->name)
            ->set('documentSearch', 'EWANUGK-ADMIN')
            ->assertSee($ownDocument->original_name)
            ->set('documentSearch', 'EWANUGK-LAIN')
            ->assertDontSee('sk-guru-lain-2026.pdf');
    }

    public function test_school_admin_can_download_own_school_decree_but_not_another_school_decree(): void
    {
        $ownDocument = $this->createDecree($this->teacher, 'sk-sendiri.pdf', 'school-decrees/sendiri.pdf');
        $otherDocument = $this->createDecree($this->otherTeacher, 'sk-lain.pdf', 'school-decrees/lain.pdf');

        $this->actingAs($this->schoolAdmin)
            ->get(route('documents.download', $ownDocument))
            ->assertOk()
            ->assertDownload('sk-sendiri.pdf');

        $this->get(route('documents.download', $otherDocument))->assertForbidden();
    }

    public function test_teacher_cannot_open_school_admin_decree_page(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('app'));

        $this->actingAs($this->teacher)
            ->get(SchoolDecrees::getUrl(panel: 'app', isAbsolute: false))
            ->assertForbidden();
    }

    private function createDecree(User $owner, string $originalName, string $path): Document
    {
        Storage::disk(Document::PRIVATE_DISK)->put($path, 'dokumen SK');

        return Document::query()->create([
            'document_type' => 'foundation_decree:2026',
            'owner_type' => User::class,
            'owner_id' => $owner->getKey(),
            'disk' => Document::PRIVATE_DISK,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => 'application/pdf',
            'size' => 10,
            'checksum' => hash('sha256', $path),
            'status' => Document::STATUS_ACTIVE,
            'uploaded_by' => $this->parentAdmin->getKey(),
        ]);
    }
}
