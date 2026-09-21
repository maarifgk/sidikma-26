<?php

namespace Tests\Feature;

use App\Filament\Imports\EmployeeImporter;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeImporterTest extends TestCase
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

    public function test_import_action_and_downloadable_example_are_available_to_admin(): void
    {
        Livewire::test(ListEmployees::class)
            ->assertActionExists('import');

        $columns = collect(EmployeeImporter::getColumns());

        $this->assertSame('employee_code', $columns->first()->getExampleHeader());
        $this->assertContains('PEG-0001', $columns->first()->getExamples());
        $this->assertSame('school_npsn', $columns->get(4)->getExampleHeader());
        $this->assertSame('user_email', $columns->get(5)->getExampleHeader());
        $this->assertTrue(EmployeeImporter::shouldPreventFormulaInjection());
    }

    public function test_import_creates_and_updates_employee_by_employee_code(): void
    {
        [$foundation, $school] = $this->createOrganization('IMPORT', '12345678');
        $user = User::factory()->create(['email' => 'akun-import@example.test']);
        $rows = [
            $this->validRow([
                'employee_code' => ' peg-import-001 ',
                'school_npsn' => $school->npsn,
                'user_email' => strtoupper($user->email),
                'name' => 'Nama Pertama',
                'is_active' => 'true',
            ]),
            $this->validRow([
                'employee_code' => 'PEG-IMPORT-001',
                'school_npsn' => $school->npsn,
                'user_email' => $user->email,
                'name' => 'Nama Hasil Pembaruan',
                'employment_status' => 'pns',
                'is_active' => 'false',
            ]),
        ];

        $import = $this->runImport($this->admin, $rows);
        $employee = Employee::query()->sole();

        $this->assertSame('PEG-IMPORT-001', $employee->employee_code);
        $this->assertSame('Nama Hasil Pembaruan', $employee->name);
        $this->assertSame('PNS', $employee->employment_status);
        $this->assertFalse($employee->is_active);
        $this->assertTrue($employee->foundation->is($foundation));
        $this->assertTrue($employee->school->is($school));
        $this->assertTrue($employee->user->is($user));
        $this->assertSame(2, $import->processed_rows);
        $this->assertSame(2, $import->successful_rows);
        $this->assertSame(0, $import->getFailedRowsCount());
    }

    public function test_invalid_rows_are_recorded_with_validation_errors(): void
    {
        [$firstFoundation, $firstSchool] = $this->createOrganization('PERTAMA', '11223344');
        $this->createOrganization('KEDUA', '55667788');
        Employee::factory()->create(['nik' => '1111222233334444']);
        $rows = [
            $this->validRow([
                'employee_code' => 'PEG-DUPLIKAT-NIK',
                'nik' => '1111222233334444',
                'school_npsn' => '',
                'user_email' => '',
            ]),
            $this->validRow([
                'employee_code' => 'PEG-SALAH-YAYASAN',
                'nik' => '9999888877776666',
                'school_npsn' => $firstSchool->npsn,
                'user_email' => '',
            ]),
        ];

        $import = $this->runImport($this->admin, $rows);

        $this->assertSame(2, $import->processed_rows);
        $this->assertSame(1, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertCount(1, $import->failedRows);
        $this->assertNotEmpty($import->failedRows->first()->validation_error);
        $this->assertDatabaseMissing('employees', ['employee_code' => 'PEG-DUPLIKAT-NIK']);
        $this->assertDatabaseHas('employees', ['employee_code' => 'PEG-SALAH-YAYASAN']);
        $this->assertTrue($firstSchool->foundation->is($firstFoundation));
    }

    public function test_import_rejects_user_account_already_assigned_to_another_employee(): void
    {
        $user = User::factory()->create(['email' => 'sudah-dipakai@example.test']);
        Employee::factory()->create(['user_id' => $user->getKey()]);
        $row = $this->validRow([
            'employee_code' => 'PEG-USER-DUPLIKAT',
            'nik' => '8888777766665555',
            'user_email' => $user->email,
        ]);

        $import = $this->runImport($this->admin, [$row]);

        $this->assertSame(0, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertStringContainsString(
            'Akun user sudah terhubung',
            $import->failedRows->sole()->validation_error,
        );
        $this->assertDatabaseMissing('employees', ['employee_code' => 'PEG-USER-DUPLIKAT']);
    }

    public function test_importer_enforces_employee_policy_for_every_row(): void
    {
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $row = $this->validRow([
            'employee_code' => 'PEG-TIDAK-BERHAK',
            'nik' => '7777666655554444',
        ]);

        $import = $this->runImport($schoolAdmin, [$row]);

        $this->assertSame(0, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertDatabaseMissing('employees', ['employee_code' => 'PEG-TIDAK-BERHAK']);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function runImport(User $user, array $rows): Import
    {
        $import = Import::query()->create([
            'file_name' => 'guru-pegawai.csv',
            'file_path' => 'testing/guru-pegawai.csv',
            'importer' => EmployeeImporter::class,
            'total_rows' => count($rows),
            'user_id' => $user->getKey(),
        ]);

        (new ImportCsv(
            import: $import,
            rows: $rows,
            columnMap: $this->columnMap(),
        ))->handle();

        return $import->refresh();
    }

    /**
     * @return array<string, string>
     */
    private function columnMap(): array
    {
        return [
            'employee_code' => 'employee_code',
            'name' => 'name',
            'employee_type' => 'employee_type',
            'employment_status' => 'employment_status',
            'school' => 'school_npsn',
            'user' => 'user_email',
            'nik' => 'nik',
            'nip' => 'nip',
            'nuptk' => 'nuptk',
            'gender' => 'gender',
            'birth_place' => 'birth_place',
            'birth_date' => 'birth_date',
            'phone' => 'phone',
            'email' => 'email',
            'address' => 'address',
            'is_active' => 'is_active',
        ];
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'employee_code' => 'PEG-VALID-001',
            'name' => 'Pegawai Import',
            'employee_type' => Employee::TYPE_GURU,
            'employment_status' => 'GTY',
            'school_npsn' => '',
            'user_email' => '',
            'nik' => '1234567890123456',
            'nip' => '',
            'nuptk' => '',
            'gender' => Employee::GENDER_MALE,
            'birth_place' => 'Gunungkidul',
            'birth_date' => '1990-05-10',
            'phone' => '+62 812-3456-7890',
            'email' => '',
            'address' => 'Jalan Pendidikan',
            'is_active' => 'true',
        ], $overrides);
    }

    /**
     * @return array{Foundation, School}
     */
    private function createOrganization(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-{$suffix}",
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Sekolah {$suffix}",
            'npsn' => $npsn,
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        return [$foundation, $school];
    }
}
