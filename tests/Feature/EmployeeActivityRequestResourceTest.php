<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeeActivityRequests\EmployeeActivityRequestResource;
use App\Filament\Resources\EmployeeActivityRequests\Pages\CreateEmployeeActivityRequest;
use App\Filament\Resources\EmployeeActivityRequests\Pages\ListEmployeeActivityRequests;
use App\Models\ActivityRequirement;
use App\Models\Employee;
use App\Models\EmployeeActivityRequest;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeActivityRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Employee $employee;

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
            'name' => 'MI YAPPI Nologaten',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $this->employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->school->getKey(),
            'name' => 'Zaini Munasir, S.Pd.I',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_activity_request_page_with_default_documents(): void
    {
        $this->assertSame(5, ActivityRequirement::query()->count());

        $this->get(EmployeeActivityRequestResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('PERMOHONAN AKTIVASI GURU')
            ->assertSee('DOKUMEN UNTUK MENGAJUKAN PERMOHONAN AKTIVASI TIDAK AKTIF')
            ->assertSee('TMT Non Aktif')
            ->assertSee('Permohonan')
            ->assertSee('Ajukan')
            ->assertSee('Edit');

        $this->get(EmployeeActivityRequestResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Input Data Permohonan Aktivasi')
            ->assertSee('NAMA LENGKAP DAN GELAR')
            ->assertSee('ASAL MADRASAH/SEKOLAH')
            ->assertSee('TMT TIDAK AKTIF')
            ->assertSee('UPLOAD SURAT PERMOHONAN (PDF)');
    }

    public function test_admin_can_submit_and_process_nonactive_request(): void
    {
        Storage::fake(EmployeeActivityRequest::DISK);

        Livewire::test(CreateEmployeeActivityRequest::class)
            ->fillForm([
                'employee_name' => $this->employee->name,
                'school_id' => $this->school->getKey(),
                'inactive_date' => '2026-08-20',
                'request_letter_path' => UploadedFile::fake()->create('permohonan-nonaktif.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $request = EmployeeActivityRequest::query()->sole();

        $this->assertTrue($request->employee->is($this->employee));
        $this->assertSame($this->employee->name, $request->employee_name);
        $this->assertSame($this->school->name, $request->school_name);
        $this->assertSame(EmployeeActivityRequest::STATUS_SUBMITTED, $request->status);
        Storage::disk(EmployeeActivityRequest::DISK)->assertExists($request->request_letter_path);

        Livewire::test(ListEmployeeActivityRequests::class)
            ->callTableAction('process', $request)
            ->assertHasNoTableActionErrors();

        $request->refresh();
        $this->employee->refresh();

        $this->assertFalse($this->employee->is_active);
        $this->assertSame(EmployeeActivityRequest::STATUS_COMPLETED, $request->status);
        $this->assertTrue($request->processedBy->is($this->admin));
        $this->assertNotNull($request->processed_at);

        $path = $request->request_letter_path;
        $request->delete();

        Storage::disk(EmployeeActivityRequest::DISK)->assertMissing($path);
    }

    public function test_admin_can_edit_activity_requirement_rows(): void
    {
        Livewire::test(ListEmployeeActivityRequests::class)
            ->callAction('editRequirements', data: [
                'items' => [
                    [
                        'id' => null,
                        'number' => '1.',
                        'description' => 'Nama guru atau pegawai terbaru.',
                        'is_active' => true,
                    ],
                    [
                        'id' => null,
                        'number' => 'KET:',
                        'description' => 'Keterangan proses terbaru.',
                        'is_active' => true,
                    ],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['Nama guru atau pegawai terbaru.', 'Keterangan proses terbaru.'],
            ActivityRequirement::query()->ordered()->pluck('description')->all(),
        );
        $this->assertSame([1, 2], ActivityRequirement::query()->ordered()->pluck('position')->all());
    }
}
