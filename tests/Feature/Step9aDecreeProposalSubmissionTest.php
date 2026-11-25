<?php

namespace Tests\Feature;

use App\Filament\Resources\DecreeProposals\Pages\CreateDecreeProposal;
use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Step9aDecreeProposalSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_school_admin_submission_creates_only_a_proposal(): void
    {
        Storage::fake('decree-proposals');
        $suffix = strtolower(str()->random(8));
        $email = "guru-9a-{$suffix}@example.test";
        $nuptk = '12345678'.strtoupper($suffix);

        $foundation = Foundation::query()->create([
            'name' => "Foundation Test 9A {$suffix}", 'code' => "F-9A-{$suffix}", 'is_active' => true,
        ]);
        $school = School::query()->create([
            'foundation_id' => $foundation->getKey(), 'name' => "School Test 9A {$suffix}",
            'npsn' => substr((string) random_int(10000000, 99999999), 0, 8), 'school_level' => 'MI', 'is_active' => true,
        ]);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $admin->getKey(), 'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(), 'status' => 'active',
        ]);
        $position = EmployeePosition::factory()->create([
            'category' => EmployeePosition::CATEGORY_TEACHING, 'name' => 'Guru Test 9A',
        ]);
        $this->actingAs($admin);

        $before = [
            'proposals' => DecreeProposal::count(), 'users' => User::count(),
            'employees' => Employee::count(), 'memberships' => Membership::count(),
            'assignments' => EmployeeAssignment::count(),
        ];

        foreach (['photos/test.jpg', 'diplomas/test.pdf', 'letters/test.pdf', 'statements/test.pdf', 'tasks/test.pdf'] as $path) {
            Storage::disk('decree-proposals')->put($path, 'dummy');
        }

        $data = [
                'employee_code' => "EWANUGK-9A-{$suffix}", 'name' => 'Guru Dummy 9A',
                'email' => $email, 'phone' => '081234567890',
                'employment_status' => 'GTY', 'birth_place' => 'Gunungkidul',
                'birth_date' => '1995-05-10', 'assignment_start_date' => '2026-07-01',
                'position_id' => $position->getKey(), 'partner_admin_number' => 'MITRA-9A',
                'nuptk' => $nuptk, 'last_education' => 'S1, 2018',
                'program_study' => 'PGMI',
            'photo_path' => 'photos/test.jpg', 'diploma_path' => 'diplomas/test.pdf',
            'application_letter_path' => 'letters/test.pdf', 'service_statement_path' => 'statements/test.pdf',
            'task_assignment_certificate_path' => 'tasks/test.pdf',
        ];

        $page = app(CreateDecreeProposal::class);
        $method = new \ReflectionMethod($page, 'handleRecordCreation');
        $method->setAccessible(true);
        $method->invoke($page, $data);

        $proposal = DecreeProposal::query()->where('candidate_email', $email)->sole();
        $this->assertSame($before['proposals'] + 1, DecreeProposal::count());
        $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $proposal->status);
        $this->assertNull($proposal->employee_id);
        $this->assertSame($school->name, $proposal->candidate_school);
        $this->assertSame($school->getKey(), $proposal->candidateSchoolId());
        $this->assertSame($before['users'], User::count());
        $this->assertSame($before['employees'], Employee::count());
        $this->assertSame($before['memberships'], Membership::count());
        $this->assertSame($before['assignments'], EmployeeAssignment::count());
        $this->assertTrue(Storage::disk('decree-proposals')->exists($proposal->photo_path));
    }
}
