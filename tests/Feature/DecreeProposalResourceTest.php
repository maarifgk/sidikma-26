<?php

namespace Tests\Feature;

use App\Filament\Resources\DecreeProposals\DecreeProposalResource;
use App\Filament\Resources\DecreeProposals\Pages\CreateDecreeProposal;
use App\Filament\Resources\DecreeProposals\Pages\ListDecreeProposals;
use App\Models\DecreeProposal;
use App\Models\DecreeRequirement;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
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

class DecreeProposalResourceTest extends TestCase
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

    public function test_default_requirements_are_available_and_admin_can_open_page(): void
    {
        $this->assertSame(9, DecreeRequirement::query()->count());

        $this->get(DecreeProposalResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('USULAN SK GURU DAN PEGAWAI BARU')
            ->assertSee('KELENGKAPAN GURU/PEGAWAI BARU')
            ->assertSee('Ajukan')
            ->assertSee('Edit')
            ->assertSee('EWANUGK');
    }

    public function test_admin_can_submit_new_decree_proposal(): void
    {
        Storage::fake('decree-proposals');

        $foundation = Foundation::query()->create([
            'name' => 'LP Ma\'arif NU Gunungkidul',
            'code' => 'LP-MAARIF-GK',
            'is_active' => true,
        ]);
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Baleharjo',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $position = EmployeePosition::factory()->create([
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'name' => 'Guru Kelas',
        ]);

        $this->get(DecreeProposalResource::getUrl('create', panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Input Data Usulan Guru &amp; Pegawai Baru', false)
            ->assertSee('NOMOR MITRA ADMIN')
            ->assertSee('SURAT PERMOHONAN (PDF)');

        Livewire::test(CreateDecreeProposal::class)
            ->fillForm([
                'employee_code' => '3403032004.1165',
                'name' => 'Naily Yumna, S.Pd',
                'email' => 'naily@example.test',
                'phone' => '081234567890',
                'school_id' => $school->getKey(),
                'employment_status' => 'GTY',
                'birth_place' => 'Gunungkidul',
                'birth_date' => '1995-05-10',
                'assignment_start_date' => '2026-07-01',
                'position_id' => $position->getKey(),
                'partner_admin_number' => 'MITRA-001',
                'nuptk' => '1234567890123456',
                'last_education' => 'S1, 2018',
                'program_study' => 'Pendidikan Guru Madrasah Ibtidaiyah',
                'application_password' => 'PasswordGuru123!',
                'application_password_confirmation' => 'PasswordGuru123!',
                'photo_path' => UploadedFile::fake()->image('foto.jpg'),
                'diploma_path' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
                'application_letter_path' => UploadedFile::fake()->create('permohonan.pdf', 100, 'application/pdf'),
                'service_statement_path' => UploadedFile::fake()->create('pernyataan.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $proposal = DecreeProposal::query()->sole();
        $employee = Employee::query()->sole();
        $assignment = EmployeeAssignment::query()->sole();
        $membership = Membership::query()->sole();
        $teacherAccount = User::query()->where('email', 'naily@example.test')->sole();

        $this->assertTrue($proposal->employee->is($employee));
        $this->assertTrue($proposal->submittedBy->is($this->admin));
        $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $proposal->status);
        $this->assertSame('MITRA-001', $proposal->partner_admin_number);
        $this->assertSame('3403032004.1165', $employee->employee_code);
        $this->assertSame($school->getKey(), $employee->school_id);
        $this->assertSame(Employee::TYPE_GURU, $employee->employee_type);
        $this->assertTrue($employee->user->is($teacherAccount));
        $this->assertTrue($teacherAccount->hasRole(User::ROLE_GURU_PEGAWAI));
        $this->assertTrue(password_verify('PasswordGuru123!', $teacherAccount->password));
        $this->assertSame($school->getKey(), $membership->school_id);
        $this->assertSame('active', $membership->status);
        $this->assertSame($position->getKey(), $assignment->employee_position_id);
        $this->assertTrue($assignment->is_primary);
        Storage::disk('decree-proposals')->assertExists($proposal->photo_path);
        Storage::disk('decree-proposals')->assertExists($proposal->diploma_path);
        Storage::disk('decree-proposals')->assertExists($proposal->application_letter_path);
        Storage::disk('decree-proposals')->assertExists($proposal->service_statement_path);
    }

    public function test_admin_can_replace_and_reorder_requirement_columns(): void
    {
        Livewire::test(ListDecreeProposals::class)
            ->callAction('editRequirements', data: [
                'items' => [
                    [
                        'id' => null,
                        'number' => '1.',
                        'description' => 'Keterangan baru pertama.',
                        'template_label' => null,
                        'template_path' => null,
                        'is_active' => true,
                    ],
                    [
                        'id' => null,
                        'number' => '2.',
                        'description' => 'Keterangan baru kedua.',
                        'template_label' => 'Unduh Contoh',
                        'template_path' => null,
                        'is_active' => true,
                    ],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['Keterangan baru pertama.', 'Keterangan baru kedua.'],
            DecreeRequirement::query()->ordered()->pluck('description')->all(),
        );
        $this->assertSame([1, 2], DecreeRequirement::query()->ordered()->pluck('position')->all());
    }

    public function test_operational_user_cannot_access_decree_proposals(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($schoolAdmin);

        $this->get(DecreeProposalResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertForbidden();
    }

    public function test_school_admin_submission_from_operational_panel_is_visible_to_admin_induk(): void
    {
        Storage::fake('decree-proposals');

        $foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'MI YAPPI Baleharjo',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);
        $position = EmployeePosition::factory()->create([
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'name' => 'Guru Kelas',
        ]);

        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->actingAs($schoolAdmin);

        $this->get(DecreeProposalResource::getUrl(panel: 'app', isAbsolute: false))
            ->assertOk()
            ->assertSee('Ajukan');

        Livewire::test(CreateDecreeProposal::class)
            ->fillForm([
                'employee_code' => 'EWANUGK-APP-001',
                'name' => 'Guru Usulan Madrasah',
                'email' => 'guru-usulan@example.test',
                'phone' => '081234567890',
                'school_id' => $school->getKey(),
                'employment_status' => 'GTY',
                'birth_place' => 'Gunungkidul',
                'birth_date' => '1995-05-10',
                'assignment_start_date' => '2026-07-01',
                'position_id' => $position->getKey(),
                'last_education' => 'S1, 2018',
                'program_study' => 'PGMI',
                'application_password' => 'PasswordGuru123!',
                'application_password_confirmation' => 'PasswordGuru123!',
                'photo_path' => UploadedFile::fake()->image('foto.jpg'),
                'diploma_path' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
                'application_letter_path' => UploadedFile::fake()->create('permohonan.pdf', 100, 'application/pdf'),
                'service_statement_path' => UploadedFile::fake()->create('pernyataan.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $proposal = DecreeProposal::query()->sole();

        Livewire::test(ListDecreeProposals::class)
            ->assertCanSeeTableRecords([$proposal]);

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->actingAs($this->admin);

        Livewire::test(ListDecreeProposals::class)
            ->assertCanSeeTableRecords([$proposal]);

        $this->assertTrue($proposal->submittedBy->is($schoolAdmin));
        $this->assertSame($school->getKey(), $proposal->employee->school_id);
    }
}
