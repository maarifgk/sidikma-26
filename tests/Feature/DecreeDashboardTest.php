<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\DecreeDashboard;
use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use App\Models\DecreeSubmission;
use App\Models\DecreeSubmissionType;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DecreeDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);
    }

    public function test_dashboard_route_and_empty_state(): void
    {
        $this->get(DecreeDashboard::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()->assertSee('Belum ada pengajuan SK.')->assertSee('Total Pengajuan SK');
    }

    public function test_dashboard_summarizes_existing_submissions_and_links_to_detail(): void
    {
        $foundation = Foundation::factory()->create();
        $school = School::create(['foundation_id' => $foundation->id, 'name' => 'MI Dashboard', 'npsn' => '99114455', 'school_level' => 'MI', 'is_active' => true]);
        $employee = Employee::factory()->create(['foundation_id' => $foundation->id, 'school_id' => $school->id]);
        foreach (DecreeSubmission::statusOptions() as $status => $label) {
            $submission = DecreeSubmission::create([
                'submission_number' => 'DASH-'.$status,
                'submission_date' => today(),
                'school_id' => $employee->school_id,
                'employee_id' => $employee->id,
                'decree_submission_type_id' => DecreeSubmissionType::firstOrFail()->id,
                'status' => $status,
                'submitted_by' => auth()->id(),
            ]);
        }
        $page = Livewire::test(DecreeDashboard::class)->assertSee($employee->name);
        $html = $page->html();
        $this->assertMatchesRegularExpression('/Total Pengajuan SK<\/p><p class="sk-number">5<\/p>/', $html);
        foreach (['Dalam Peninjauan', 'Sedang Diproses', 'Selesai'] as $label) {
            $this->assertStringContainsString($label.'</p><p class="sk-number">1</p>', $html);
        }
        $detail = DecreeSubmissionResource::getUrl('view', ['record' => $submission], panel: 'admin', isAbsolute: false);
        $page->assertSee($detail, false);
        $this->get($detail)->assertOk();
        $this->get(DecreeSubmissionResource::getUrl('index', panel: 'admin', isAbsolute: false))->assertOk();
    }

    public function test_template_handles_missing_relations(): void
    {
        $submission = new DecreeSubmission(['status' => DecreeSubmission::STATUS_UNDER_REVIEW]);
        $submission->setRelation('employee', null)->setRelation('school', null)->setRelation('type', null);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->view('filament.admin.pages.decree-dashboard', [
            'summary' => [], 'submissions' => collect([$submission]),
            'statuses' => DecreeSubmission::statusOptions(), 'listUrl' => '/admin/decree-submissions',
        ])->assertSee('Dalam Peninjauan')->assertSee('-');
    }

    public function test_dashboard_rejects_users_without_existing_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->actingAs($user)->get(DecreeDashboard::getUrl(panel: 'admin', isAbsolute: false))->assertForbidden();
        $this->assertFalse(DecreeDashboard::canAccess());
    }
}
