<?php

namespace Tests\Feature;

use App\Filament\Resources\SchoolHeads\SchoolHeadResource;
use App\Filament\Resources\SchoolHeads\Pages\EditSchoolHead;
use App\Filament\Resources\SchoolHeads\Pages\CreateSchoolHead;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\SchoolHead;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolHeadResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_induk_can_open_all_school_head_pages(): void
    {
        [$school, $employee] = $this->createSchoolAndEmployee('INDUK', '31000001');
        $head = $this->createHead($school, $employee);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $this->get(SchoolHeadResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Data Kepala Madrasah/Sekolah')
            ->assertSee($employee->name);
        $this->get(SchoolHeadResource::getUrl('create', panel: 'admin', isAbsolute: false))->assertOk();
        $this->get(SchoolHeadResource::getUrl('view', ['record' => $head], panel: 'admin', isAbsolute: false))->assertOk();
        $this->get(SchoolHeadResource::getUrl('edit', ['record' => $head], panel: 'admin', isAbsolute: false))->assertOk();
        $this->get('/admin')->assertOk()->assertSee('Data Kepala Madrasah/Sekolah');
    }

    public function test_school_admin_only_accesses_own_school_head(): void
    {
        [$ownSchool, $ownEmployee] = $this->createSchoolAndEmployee('SENDIRI', '31000002');
        [$otherSchool, $otherEmployee] = $this->createSchoolAndEmployee('LAIN', '31000003');
        $ownHead = $this->createHead($ownSchool, $ownEmployee);
        $otherHead = $this->createHead($otherSchool, $otherEmployee);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $admin->getKey(),
            'foundation_id' => $ownSchool->foundation_id,
            'school_id' => $ownSchool->getKey(),
            'status' => 'active',
        ]);
        $this->actingAs($admin);

        $index = SchoolHeadResource::getUrl(panel: 'app', isAbsolute: false);
        $this->get($index)
            ->assertOk()
            ->assertSee($ownEmployee->name)
            ->assertDontSee($otherEmployee->name);
        $this->get(SchoolHeadResource::getUrl('view', ['record' => $ownHead], panel: 'app', isAbsolute: false))->assertOk();
        $this->get(SchoolHeadResource::getUrl('view', ['record' => $otherHead], panel: 'app', isAbsolute: false))->assertNotFound();
        $this->get('/app')->assertOk()->assertSee('Data Kepala Madrasah/Sekolah');
    }

    public function test_teacher_cannot_access_school_head_resource(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->actingAs($teacher);

        $this->get('/app/school-heads')->assertForbidden();
    }

    public function test_admin_can_upload_private_pdf_for_latest_head_decree(): void
    {
        Storage::fake('documents');
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        [$school, $employee] = $this->createSchoolAndEmployee('UPLOAD', '31000004');
        $head = $this->createHead($school, $employee);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        Livewire::test(EditSchoolHead::class, ['record' => $head->getRouteKey()])
            ->set('data.latest_sk_upload', UploadedFile::fake()->create('SK Kepala Terbaru.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoFormErrors();

        $document = $head->documents()->sole();
        $this->assertSame('sk', $document->document_type);
        $this->assertSame('SK Kepala Terbaru.pdf', $document->original_name);
        Storage::disk('documents')->assertExists($document->path);
    }

    public function test_admin_can_upload_head_decree_while_creating_record(): void
    {
        Storage::fake('documents');
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        [$school, $employee] = $this->createSchoolAndEmployee('CREATE-UPLOAD', '31000005');
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        Livewire::test(CreateSchoolHead::class)
            ->set('data.school_id', $school->getKey())
            ->set('data.employee_id', $employee->getKey())
            ->set('data.status', SchoolHead::STATUS_ACTIVE)
            ->set('data.position', 'Kepala Madrasah/Sekolah')
            ->set('data.latest_sk_upload', UploadedFile::fake()->create('SK Kepala Baru.pdf', 100, 'application/pdf'))
            ->call('create')
            ->assertHasNoFormErrors();

        $head = SchoolHead::query()->sole();
        $document = $head->documents()->sole();
        $this->assertSame('SK Kepala Baru.pdf', $document->original_name);
        $this->assertStringStartsWith("school-heads/{$head->getKey()}/", $document->path);
        Storage::disk('documents')->assertExists($document->path);
    }

    /** @return array{School, Employee} */
    private function createSchoolAndEmployee(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-RESOURCE-HEAD-{$suffix}",
            'is_active' => true,
        ]);
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Sekolah {$suffix}",
            'npsn' => $npsn,
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
        ]);

        return [$school, $employee];
    }

    private function createHead(School $school, Employee $employee): SchoolHead
    {
        return SchoolHead::query()->create([
            'school_id' => $school->getKey(),
            'employee_id' => $employee->getKey(),
            'status' => SchoolHead::STATUS_ACTIVE,
            'started_at' => '2026-07-01',
            'sk_start_date' => '2026-07-01',
            'sk_end_date' => '2030-06-30',
            'latest_sk_number' => 'SK/HEAD/2026',
        ]);
    }
}
