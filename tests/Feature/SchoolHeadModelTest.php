<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\SchoolHead;
use App\Models\SchoolHeadAchievement;
use App\Models\SchoolHeadCertificate;
use App\Models\SchoolHeadJobHistory;
use App\Models\SchoolHeadTraining;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolHeadModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(User::ROLE_ADMIN_INDUK, 'web');
        Role::findOrCreate(User::ROLE_ADMIN_SEKOLAH_MADRASAH, 'web');
        $this->travelTo('2026-09-01 08:00:00');
    }

    public function test_school_head_tables_have_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('school_heads', [
            'school_id', 'employee_id', 'status', 'position', 'started_at',
            'sk_start_date', 'sk_end_date', 'latest_sk_number', 'created_by',
            'updated_by', 'deleted_at',
        ]));
        $this->assertTrue(Schema::hasTable('school_head_job_histories'));
        $this->assertTrue(Schema::hasTable('school_head_certificates'));
        $this->assertTrue(Schema::hasTable('school_head_trainings'));
        $this->assertTrue(Schema::hasTable('school_head_achievements'));
    }

    public function test_relations_and_sk_validity_status_work(): void
    {
        [$school, $employee] = $this->createSchoolAndEmployee('SATU', '11112222');
        $head = $this->createHead($school, $employee, ['sk_end_date' => '2026-10-15']);

        $history = SchoolHeadJobHistory::query()->create([
            'school_head_id' => $head->getKey(),
            'started_at' => '2020-01-01',
            'position' => 'Kepala Madrasah',
            'institution_name' => 'MI Sebelumnya',
        ]);
        $certificate = SchoolHeadCertificate::query()->create([
            'school_head_id' => $head->getKey(),
            'name' => 'Sertifikat Kepala Madrasah',
        ]);
        $training = SchoolHeadTraining::query()->create([
            'school_head_id' => $head->getKey(),
            'name' => 'Diklat Kepala Madrasah',
            'activity_type' => 'diklat',
        ]);
        $achievement = SchoolHeadAchievement::query()->create([
            'school_head_id' => $head->getKey(),
            'name' => 'Kepala Madrasah Berprestasi',
            'level' => 'kabupaten',
        ]);

        $this->assertTrue($head->school->is($school));
        $this->assertTrue($head->employee->is($employee));
        $this->assertTrue($school->activeSchoolHead->is($head));
        $this->assertTrue($employee->schoolHeads->contains($head));
        $this->assertTrue($head->jobHistories->contains($history));
        $this->assertTrue($head->certificates->contains($certificate));
        $this->assertTrue($head->trainings->contains($training));
        $this->assertTrue($head->achievements->contains($achievement));
        $this->assertSame('expiring', $head->sk_validity_status);
    }

    public function test_employee_must_belong_to_selected_school(): void
    {
        [$firstSchool] = $this->createSchoolAndEmployee('PERTAMA', '33334444');
        [$secondSchool, $secondEmployee] = $this->createSchoolAndEmployee('KEDUA', '55556666');

        try {
            $this->createHead($firstSchool, $secondEmployee);
            $this->fail('Kepala dari sekolah lain seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Guru/pegawai harus berasal dari sekolah/madrasah yang dipilih.',
                $exception->errors()['employee_id'][0],
            );
        }

        $this->assertDatabaseCount('school_heads', 0);
        $this->assertNotSame($firstSchool->getKey(), $secondSchool->getKey());
    }

    public function test_school_only_has_one_active_head(): void
    {
        [$school, $firstEmployee] = $this->createSchoolAndEmployee('AKTIF', '77778888');
        $secondEmployee = Employee::factory()->create([
            'foundation_id' => $school->foundation_id,
            'school_id' => $school->getKey(),
        ]);
        $this->createHead($school, $firstEmployee);

        $this->expectException(ValidationException::class);
        $this->createHead($school, $secondEmployee);
    }

    public function test_school_admin_scope_only_returns_own_school(): void
    {
        [$firstSchool, $firstEmployee] = $this->createSchoolAndEmployee('AKSES-1', '10101010');
        [$secondSchool, $secondEmployee] = $this->createSchoolAndEmployee('AKSES-2', '20202020');
        $this->createHead($firstSchool, $firstEmployee);
        $this->createHead($secondSchool, $secondEmployee);

        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $schoolAdmin->getKey(),
            'foundation_id' => $firstSchool->foundation_id,
            'school_id' => $firstSchool->getKey(),
            'status' => 'active',
        ]);

        $adminInduk = User::factory()->create();
        $adminInduk->assignRole(User::ROLE_ADMIN_INDUK);

        $this->assertCount(1, SchoolHead::query()->accessibleTo($schoolAdmin)->get());
        $this->assertCount(2, SchoolHead::query()->accessibleTo($adminInduk)->get());
    }

    /** @return array{School, Employee} */
    private function createSchoolAndEmployee(string $suffix, string $npsn): array
    {
        $foundation = Foundation::query()->create([
            'name' => "Yayasan {$suffix}",
            'code' => "YYS-HEAD-{$suffix}",
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

    /** @param array<string, mixed> $overrides */
    private function createHead(School $school, Employee $employee, array $overrides = []): SchoolHead
    {
        return SchoolHead::query()->create(array_merge([
            'school_id' => $school->getKey(),
            'employee_id' => $employee->getKey(),
            'status' => SchoolHead::STATUS_ACTIVE,
            'started_at' => '2026-07-01',
            'sk_start_date' => '2026-07-01',
            'sk_end_date' => '2030-06-30',
            'latest_sk_number' => 'SK/001/2026',
        ], $overrides));
    }
}
