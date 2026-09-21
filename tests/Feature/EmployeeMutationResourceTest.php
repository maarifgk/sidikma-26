<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeeMutations\EmployeeMutationResource;
use App\Filament\Resources\EmployeeMutations\Pages\CreateEmployeeMutation;
use App\Filament\Resources\EmployeeMutations\Pages\ListEmployeeMutations;
use App\Models\Employee;
use App\Models\EmployeeMutation;
use App\Models\Foundation;
use App\Models\MutationRequirement;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeMutationResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $originSchool;

    private School $destinationSchool;

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
        $this->originSchool = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Karang',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $this->destinationSchool = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Dondong',
            'npsn' => '87654321',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $this->employee = Employee::factory()->create([
            'foundation_id' => $foundation->getKey(),
            'school_id' => $this->originSchool->getKey(),
            'employee_code' => '3403032004.1165',
            'name' => 'Yusefiah Nur Iva Fadhillah',
            'phone' => '089669311342',
        ]);
    }

    public function test_admin_can_open_mutation_page_with_default_requirements(): void
    {
        $this->assertSame(2, MutationRequirement::query()->count());
        $this->assertSame([
            EmployeeMutation::TYPE_INTERNAL => "Mutasi Internal dalam Ma'arif",
            EmployeeMutation::TYPE_OUTGOING => "Mutasi Keluar dari Ma'arif",
            EmployeeMutation::TYPE_INCOMING => "Mutasi Masuk ke Ma'arif",
        ], EmployeeMutation::typeOptions());

        $this->get(EmployeeMutationResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('MUTASI GURU &amp; PEGAWAI', false)
            ->assertSee('KELENGKAPAN MUTASI GURU')
            ->assertSee('Ajukan')
            ->assertSee('EWANUGK')
            ->assertSee('Sekolah/Madrasah Tujuan');

        $this->get(EmployeeMutationResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Input Data Usulan Mutasi')
            ->assertSee('STATUS KEPEGAWAIAN')
            ->assertSee('UPLOAD SURAT PERMOHONAN MUTASI (PDF)');
    }

    public function test_admin_can_submit_employee_mutation_and_file_is_private(): void
    {
        Storage::fake(EmployeeMutation::DISK);
        $employeeBefore = $this->employee->fresh()->getAttributes();

        Livewire::test(CreateEmployeeMutation::class)
            ->set('data.employee_code', $this->employee->employee_code)
            ->assertSet('data.employment_status', $this->employee->employment_status)
            ->fillForm([
                'employee_code' => $this->employee->employee_code,
                'employee_name' => $this->employee->name,
                'phone' => $this->employee->phone,
                'birth_place' => 'Gunungkidul',
                'birth_date' => '1990-05-10',
                'mutation_type' => EmployeeMutation::TYPE_OUTGOING,
                'effective_date' => '2026-08-15',
                'origin_school_id' => $this->originSchool->id,
                'request_letter_path' => UploadedFile::fake()->create('surat-mutasi.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $mutation = EmployeeMutation::query()->sole();

        $this->assertTrue($mutation->employee->is($this->employee));
        $this->assertTrue($mutation->originSchool->is($this->originSchool));
        $this->assertNull($mutation->destination_school_id);
        $this->assertTrue($mutation->submittedBy->is($this->admin));
        $this->assertSame(EmployeeMutation::TYPE_OUTGOING, $mutation->mutation_type);
        $this->assertSame($this->employee->employee_code, $mutation->employee_code);
        $this->assertSame($this->originSchool->name, $mutation->origin_school_name);
        $this->assertNull($mutation->destination_school_name);
        $this->assertNull($mutation->effective_date);
        $this->assertSame($this->employee->employment_status, $mutation->employment_status);
        $this->assertSame($employeeBefore, $this->employee->fresh()->getAttributes());
        Storage::disk(EmployeeMutation::DISK)->assertExists($mutation->request_letter_path);

        $path = $mutation->request_letter_path;
        $mutation->delete();

        Storage::disk(EmployeeMutation::DISK)->assertMissing($path);
    }

    public function test_admin_can_edit_mutation_requirements(): void
    {
        Storage::fake(EmployeeMutation::DISK);

        Livewire::test(ListEmployeeMutations::class)
            ->callAction('editRequirements', data: [
                'items' => [
                    [
                        'id' => null,
                        'number' => '1.',
                        'description' => 'Surat permohonan mutasi terbaru.',
                        'template_label' => 'Download PDF',
                        'template_path' => [
                            UploadedFile::fake()->create(
                                'Template Surat Mutasi.docx',
                                32,
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ),
                        ],
                        'is_active' => true,
                    ],
                ],
            ])
            ->assertHasNoFormErrors();

        $requirement = MutationRequirement::query()->sole();

        $this->assertSame(1, $requirement->position);
        $this->assertSame('Surat permohonan mutasi terbaru.', $requirement->description);
        $this->assertSame('templates/Template Surat Mutasi.docx', $requirement->template_path);
        Storage::disk(EmployeeMutation::DISK)->assertExists($requirement->template_path);
    }

    public function test_authenticated_admin_can_download_private_mutation_template(): void
    {
        Storage::fake(EmployeeMutation::DISK);
        $path = 'requirement-templates/template-mutasi.docx';
        Storage::disk(EmployeeMutation::DISK)->put($path, 'dokumen word');
        $requirement = MutationRequirement::query()->create([
            'position' => 1,
            'number' => '1.',
            'description' => 'Template mutasi',
            'template_label' => 'Download Word',
            'template_path' => $path,
            'is_active' => true,
            'created_by' => $this->admin->getKey(),
        ]);

        $this->get(route('administration-templates.download', [
            'type' => 'mutation',
            'record' => $requirement,
        ]))
            ->assertOk()
            ->assertDownload('template-mutasi.docx');
    }

    public function test_incoming_mutation_can_be_submitted_for_person_not_yet_in_master_data(): void
    {
        Storage::fake(EmployeeMutation::DISK);

        Livewire::test(CreateEmployeeMutation::class)
            ->fillForm([
                'employee_code' => null,
                'employee_name' => 'Guru dari Luar Ma\'arif',
                'employment_status' => 'GTT',
                'phone' => '081234567890',
                'birth_place' => 'Yogyakarta',
                'birth_date' => '1992-04-10',
                'mutation_type' => EmployeeMutation::TYPE_INCOMING,
                'effective_date' => '2026-08-20',
                'origin_school_name' => 'Sekolah Asal di Luar Ma\'arif',
                'destination_school_id' => $this->destinationSchool->id,
                'request_letter_path' => UploadedFile::fake()->create('mutasi-masuk.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $mutation = EmployeeMutation::query()->sole();

        $this->assertNull($mutation->employee_id);
        $this->assertNull($mutation->employee_code);
        $this->assertNull($mutation->origin_school_id);
        $this->assertSame($this->destinationSchool->getKey(), $mutation->destination_school_id);
        $this->assertSame('Guru dari Luar Ma\'arif', $mutation->employee_name);
        $this->assertSame('GTT', $mutation->employment_status);
        $this->assertSame('Sekolah Asal di Luar Ma\'arif', $mutation->origin_school_name);
        $this->assertSame(1, Employee::query()->count());
    }

    public function test_switching_to_incoming_allows_typed_origin_without_changing_master_data(): void
    {
        Storage::fake(EmployeeMutation::DISK);
        $employeeBefore = $this->employee->fresh()->getAttributes();
        $schoolBefore = $this->originSchool->fresh()->getAttributes();

        Livewire::test(CreateEmployeeMutation::class)
            ->set('data.mutation_type', EmployeeMutation::TYPE_INTERNAL)
            ->set('data.origin_school_id', $this->originSchool->id)
            ->set('data.mutation_type', EmployeeMutation::TYPE_INCOMING)
            ->assertSet('data.origin_school_id', null)
            ->fillForm([
                'employee_name' => 'Guru Baru',
                'employment_status' => 'GTY_SERTIFIKASI_INPASSING',
                'birth_place' => 'Yogyakarta',
                'birth_date' => '1992-04-10',
                'phone' => '081234567890',
                'origin_school_name' => 'Madrasah dari Luar Ma\'arif',
                'destination_school_id' => $this->destinationSchool->id,
                'request_letter_path' => UploadedFile::fake()->create('masuk.pdf', 100, 'application/pdf'),
            ])
            ->call('create')->assertHasNoFormErrors();

        $mutation = EmployeeMutation::query()->sole();
        $this->assertNull($mutation->origin_school_id);
        $this->assertSame('Madrasah dari Luar Ma\'arif', $mutation->origin_school_name);
        $this->assertSame('GTY_SERTIFIKASI_INPASSING', $mutation->employment_status);
        $this->assertSame($employeeBefore, $this->employee->fresh()->getAttributes());
        $this->assertSame($schoolBefore, $this->originSchool->fresh()->getAttributes());
        $this->assertSame(2, School::query()->count());
    }

    public function test_status_options_follow_educator_accounts_and_reset_when_code_changes(): void
    {
        $this->employee->update(['employment_status' => 'GTY_SERTIFIKASI_NON_INPASSING']);
        Livewire::test(CreateEmployeeMutation::class)
            ->set('data.employee_code', $this->employee->employee_code)
            ->assertSet('data.employment_status', 'GTY_SERTIFIKASI_NON_INPASSING')
            ->assertSee(Employee::employmentStatusLabel('GTY_SERTIFIKASI_NON_INPASSING'))
            ->set('data.employee_code', null)
            ->assertSet('data.employment_status', null)
            ->set('data.mutation_type', EmployeeMutation::TYPE_INCOMING)
            ->set('data.employment_status', 'STATUS_TIDAK_VALID')
            ->call('create')->assertHasFormErrors(['employment_status']);
        $this->assertSame(0, EmployeeMutation::query()->count());
    }

    public function test_admin_can_download_mutation_template_pdf(): void
    {
        $response = $this->get(route('mutations.template.download'));

        $response
            ->assertOk()
            ->assertDownload('template-surat-mutasi-guru-pegawai.pdf')
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
    }
}
