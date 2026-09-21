<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\BatikOrder;
use App\Models\BatikProduct;
use App\Models\Complaint;
use App\Models\CorrespondenceRequest;
use App\Models\DecreeProposal;
use App\Models\DecreeSubmission;
use App\Models\EducatorRecap;
use App\Models\EmployeeActivityRequest;
use App\Models\EmployeeMutation;
use App\Models\Foundation;
use App\Models\FoundationAnnualReport;
use App\Models\FoundationWorkProgram;
use App\Models\LearningModule;
use App\Models\PaymentFeeItem;
use App\Models\PaymentInvoice;
use App\Models\PaymentType;
use App\Models\ProposalRequest;
use App\Models\School;
use App\Models\SecretariatAgenda;
use App\Models\SipinterUpdate;
use App\Models\StudentEnrollment;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Notifications\SidikmaResetPassword;
use App\Observers\UserObserver;
use App\Policies\AcademicYearPolicy;
use App\Policies\ActivityPolicy;
use App\Policies\BatikOrderPolicy;
use App\Policies\BatikProductPolicy;
use App\Policies\ComplaintPolicy;
use App\Policies\CorrespondenceRequestPolicy;
use App\Policies\DecreeProposalPolicy;
use App\Policies\DecreeSubmissionPolicy;
use App\Policies\EducatorRecapPolicy;
use App\Policies\EmployeeActivityRequestPolicy;
use App\Policies\EmployeeMutationPolicy;
use App\Policies\FoundationAnnualReportPolicy;
use App\Policies\FoundationPolicy;
use App\Policies\FoundationWorkProgramPolicy;
use App\Policies\LearningModulePolicy;
use App\Policies\PaymentFeeItemPolicy;
use App\Policies\PaymentInvoicePolicy;
use App\Policies\PaymentTypePolicy;
use App\Policies\ProposalRequestPolicy;
use App\Policies\SchoolPolicy;
use App\Policies\SecretariatAgendaPolicy;
use App\Policies\SipinterUpdatePolicy;
use App\Policies\StudentEnrollmentPolicy;
use App\Policies\TreasuryTransactionPolicy;
use App\Policies\UserPolicy;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\FoundationSkDocument;
use App\Policies\FoundationSkDocumentPolicy;
use App\Models\FoundationSkTemplate;
use App\Models\FoundationSkNumberSetting;
use App\Policies\FoundationSkTemplatePolicy;
use App\Policies\FoundationSkNumberSettingPolicy;
use App\Contracts\FoundationSkPdfWriter;
use App\Contracts\FoundationSkDocumentWriter;
use App\Contracts\PaymentGatewayInterface;
use App\Services\Payments\LocalPaymentGateway;
use App\Services\FoundationSkPdfWriterImplementation;
use App\Services\FoundationSkDocumentWriterImplementation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FoundationSkPdfWriter::class, FoundationSkPdfWriterImplementation::class);
        $this->app->bind(FoundationSkDocumentWriter::class, FoundationSkDocumentWriterImplementation::class);
        $this->app->bind(PaymentGatewayInterface::class, LocalPaymentGateway::class);
        $this->app->bind(FilamentResetPassword::class, SidikmaResetPassword::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(FoundationSkDocument::class, FoundationSkDocumentPolicy::class);
        Gate::policy(FoundationSkTemplate::class, FoundationSkTemplatePolicy::class);
        Gate::policy(FoundationSkNumberSetting::class, FoundationSkNumberSettingPolicy::class);
        Gate::policy(AcademicYear::class, AcademicYearPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(DecreeSubmission::class, DecreeSubmissionPolicy::class);
        Gate::policy(BatikOrder::class, BatikOrderPolicy::class);
        Gate::policy(BatikProduct::class, BatikProductPolicy::class);
        Gate::policy(CorrespondenceRequest::class, CorrespondenceRequestPolicy::class);
        Gate::policy(DecreeProposal::class, DecreeProposalPolicy::class);
        Gate::policy(EmployeeMutation::class, EmployeeMutationPolicy::class);
        Gate::policy(EducatorRecap::class, EducatorRecapPolicy::class);
        Gate::policy(EmployeeActivityRequest::class, EmployeeActivityRequestPolicy::class);
        Gate::policy(Complaint::class, ComplaintPolicy::class);
        Gate::policy(Foundation::class, FoundationPolicy::class);
        Gate::policy(FoundationAnnualReport::class, FoundationAnnualReportPolicy::class);
        Gate::policy(FoundationWorkProgram::class, FoundationWorkProgramPolicy::class);
        Gate::policy(LearningModule::class, LearningModulePolicy::class);
        Gate::policy(PaymentFeeItem::class, PaymentFeeItemPolicy::class);
        Gate::policy(PaymentInvoice::class, PaymentInvoicePolicy::class);
        Gate::policy(PaymentType::class, PaymentTypePolicy::class);
        Gate::policy(ProposalRequest::class, ProposalRequestPolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(SecretariatAgenda::class, SecretariatAgendaPolicy::class);
        Gate::policy(SipinterUpdate::class, SipinterUpdatePolicy::class);
        Gate::policy(StudentEnrollment::class, StudentEnrollmentPolicy::class);
        Gate::policy(TreasuryTransaction::class, TreasuryTransactionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        User::observe(UserObserver::class);

    }
}
