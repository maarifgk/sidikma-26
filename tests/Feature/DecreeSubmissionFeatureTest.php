<?php

namespace Tests\Feature;

use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use App\Filament\Resources\DecreeSubmissions\Pages\CreateDecreeSubmission;
use App\Filament\Resources\DecreeSubmissions\Pages\EditDecreeSubmission;
use App\Models\DecreeSubmission;
use App\Models\DecreeSubmissionType;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\DecreeSubmissionEligibility;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DecreeSubmissionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $schoolAdmin;
    private User $adminInduk;
    private School $school;
    private Employee $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $foundation = Foundation::query()->create(['name' => 'Yayasan Pengujian SK', 'code' => 'YYS-PSK', 'is_active' => true]);
        $this->school = School::query()->create(['foundation_id' => $foundation->getKey(), 'name' => 'MI Pengujian SK', 'npsn' => '99110001', 'school_level' => 'MI', 'is_active' => true]);
        $this->schoolAdmin = User::factory()->create();
        $this->schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create(['user_id' => $this->schoolAdmin->getKey(), 'foundation_id' => $foundation->getKey(), 'school_id' => $this->school->getKey(), 'status' => 'active', 'start_date' => today()]);
        $this->teacher = Employee::factory()->create(['school_id' => $this->school->getKey(), 'foundation_id' => $foundation->getKey(), 'employee_type' => Employee::TYPE_GURU, 'is_active' => true]);
        $this->adminInduk = User::factory()->create();
        $this->adminInduk->assignRole(User::ROLE_ADMIN_INDUK);
    }

    public function test_school_must_have_only_paid_current_year_payments_to_submit(): void
    {
        $eligibility = app(DecreeSubmissionEligibility::class);
        $this->assertFalse($eligibility->schoolCanSubmit($this->school));
        $invoice = $this->invoice(PaymentInvoice::STATUS_UNPAID);
        $this->assertFalse($eligibility->schoolCanSubmit($this->school));
        $invoice->update(['status' => PaymentInvoice::STATUS_PAID, 'paid_at' => now()]);
        $this->assertTrue($eligibility->schoolCanSubmit($this->school));
    }

    public function test_school_admin_can_submit_for_teacher_in_own_school_and_admin_is_notified(): void
    {
        $this->invoice(PaymentInvoice::STATUS_PAID);
        $this->actingAs($this->schoolAdmin);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $type = DecreeSubmissionType::query()->firstOrFail();

        Livewire::test(CreateDecreeSubmission::class)
            ->set('data.submission_date', today()->format('Y-m-d'))
            ->set('data.employee_id', $this->teacher->getKey())
            ->set('data.decree_submission_type_id', $type->getKey())
            ->set('data.purpose', 'Pembuatan SK tahun berjalan.')
            ->call('create')
            ->assertHasNoFormErrors();

        $submission = DecreeSubmission::query()->sole();
        $this->assertSame($this->school->getKey(), $submission->school_id);
        $this->assertSame(DecreeSubmission::STATUS_UNDER_REVIEW, $submission->status);
        $this->assertCount(1, $submission->statusHistories);
        $this->assertSame('Pengajuan SK baru', $this->adminInduk->notifications()->latest()->first()?->data['title']);
    }

    public function test_school_admin_cannot_select_teacher_or_view_submission_from_other_school(): void
    {
        $otherFoundation = Foundation::query()->create(['name' => 'Yayasan Lain', 'code' => 'YYS-LAIN-PSK', 'is_active' => true]);
        $otherSchool = School::query()->create(['foundation_id' => $otherFoundation->getKey(), 'name' => 'MI Sekolah Lain', 'npsn' => '99110002', 'school_level' => 'MI', 'is_active' => true]);
        $otherTeacher = Employee::factory()->create(['foundation_id' => $otherSchool->foundation_id, 'school_id' => $otherSchool->getKey(), 'employee_type' => Employee::TYPE_GURU]);
        $this->invoice(PaymentInvoice::STATUS_PAID);
        $this->actingAs($this->schoolAdmin);
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $type = DecreeSubmissionType::query()->firstOrFail();

        Livewire::test(CreateDecreeSubmission::class)
            ->set('data.submission_date', today()->format('Y-m-d'))
            ->set('data.employee_id', $otherTeacher->getKey())
            ->set('data.decree_submission_type_id', $type->getKey())
            ->call('create')
            ->assertHasFormErrors(['employee_id']);
        $this->assertDatabaseCount('decree_submissions', 0);

        $submission = $this->submissionFor($otherTeacher, $otherSchool, $type);
        $this->get(DecreeSubmissionResource::getUrl('view', ['record' => $submission], panel: 'app', isAbsolute: false))->assertNotFound();
    }

    public function test_only_admin_induk_can_finish_with_pdf_and_school_can_download(): void
    {
        Storage::fake('documents');
        $type = DecreeSubmissionType::query()->firstOrFail();
        $submission = $this->submissionFor($this->teacher, $this->school, $type);
        $this->actingAs($this->adminInduk);
        filament()->setCurrentPanel(filament()->getPanel('admin'));

        Livewire::test(EditDecreeSubmission::class, ['record' => $submission->getRouteKey()])
            ->set('data.status', DecreeSubmission::STATUS_COMPLETED)
            ->call('save')
            ->assertHasFormErrors(['result_file_path']);

        Livewire::test(EditDecreeSubmission::class, ['record' => $submission->getRouteKey()])
            ->set('data.status', DecreeSubmission::STATUS_COMPLETED)
            ->set('data.result_file_path', UploadedFile::fake()->create('hasil-sk.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoFormErrors();

        $submission->refresh();
        $this->assertNotNull($submission->completed_at);
        $this->assertCount(2, $submission->statusHistories);
        $this->actingAs($this->schoolAdmin);
        $this->get(route('decree-submissions.download', $submission))->assertOk();
    }

    private function invoice(string $status): PaymentInvoice
    {
        return PaymentInvoice::query()->create(['foundation_id' => $this->school->foundation_id, 'school_id' => $this->school->getKey(), 'user_id' => $this->schoolAdmin->getKey(), 'academic_year' => StudentEnrollment::currentAcademicYear(), 'invoice_number' => PaymentInvoice::generateInvoiceNumber(), 'description' => 'Iuran Tahunan', 'amount' => 25000, 'status' => $status]);
    }

    private function submissionFor(Employee $teacher, School $school, DecreeSubmissionType $type): DecreeSubmission
    {
        $submission = DecreeSubmission::query()->create(['submission_number' => DecreeSubmission::generateSubmissionNumber(), 'submission_date' => today(), 'school_id' => $school->getKey(), 'employee_id' => $teacher->getKey(), 'decree_submission_type_id' => $type->getKey(), 'status' => DecreeSubmission::STATUS_UNDER_REVIEW, 'submitted_by' => $this->schoolAdmin->getKey()]);
        $submission->statusHistories()->create(['to_status' => DecreeSubmission::STATUS_UNDER_REVIEW, 'changed_by' => $this->schoolAdmin->getKey()]);
        return $submission;
    }
}
