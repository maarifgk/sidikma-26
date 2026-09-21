<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Resources\DecreeCorrectionRequests\DecreeCorrectionRequestResource;
use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use App\Models\DecreeCorrectionRequest;
use App\Models\DecreeSubmission;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\PaymentInvoice;
use App\Models\StudentEnrollment;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getHeading(): ?string
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.pages.modern-dashboard')
                ->viewData(fn (): array => $this->dashboardData()),
        ]);
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    private function dashboardData(): array
    {
        $academicYear = StudentEnrollment::currentAcademicYear();
        $invoices = PaymentInvoice::query()->with(['school', 'user'])->get();
        $paidInvoices = $invoices->where('status', PaymentInvoice::STATUS_PAID);
        $unpaidInvoices = $invoices->where('status', PaymentInvoice::STATUS_UNPAID);
        $months = collect(range(1, 12))->map(function (int $month) use ($paidInvoices): array {
            $amount = (float) $paidInvoices->filter(fn (PaymentInvoice $invoice): bool => ($invoice->paid_at?->month ?? $invoice->updated_at?->month) === $month)->sum('amount');

            return ['label' => now('Asia/Jakarta')->month($month)->locale('id')->translatedFormat('M'), 'amount' => $amount];
        });
        $maxMonthly = max(1, (float) $months->max('amount'));
        $statusCounts = Employee::query()->where('is_active', true)->selectRaw('employment_status, count(*) as total')->groupBy('employment_status')->pluck('total', 'employment_status');
        $decreeSubmissions = DecreeSubmission::query()->with(['school', 'employee'])->latest('submission_date')->limit(5)->get()->map(fn (DecreeSubmission $item): array => [
            'number' => $item->submission_number, 'date' => $item->submission_date, 'school' => $item->school?->name ?? '-', 'name' => $item->employee?->name ?? '-', 'status' => DecreeSubmission::statusOptions()[$item->status] ?? $item->status, 'color' => match ($item->status) {
                DecreeSubmission::STATUS_COMPLETED => 'success', DecreeSubmission::STATUS_REJECTED => 'danger', DecreeSubmission::STATUS_PROCESSING => 'info', default => 'warning'
            }, 'url' => DecreeSubmissionResource::getUrl('view', ['record' => $item], panel: 'admin', isAbsolute: false),
        ]);
        $corrections = DecreeCorrectionRequest::query()->with('school')->latest('request_date')->limit(5)->get()->map(fn (DecreeCorrectionRequest $item): array => [
            'number' => $item->request_number, 'date' => $item->request_date, 'school' => $item->school?->name ?? '-', 'name' => $item->subject_name, 'status' => DecreeCorrectionRequest::statusOptions()[$item->status] ?? $item->status, 'color' => DecreeCorrectionRequest::statusColor($item->status), 'url' => DecreeCorrectionRequestResource::getUrl('view', ['record' => $item], panel: 'admin', isAbsolute: false),
        ]);

        return [
            'academicYear' => $academicYear,
            'paidAmount' => (float) $paidInvoices->sum('amount'),
            'unpaidAmount' => (float) $unpaidInvoices->sum('amount'),
            'months' => $months->map(fn (array $row): array => [...$row, 'percentage' => (int) round(($row['amount'] / $maxMonthly) * 100)]),
            'recentPayments' => $paidInvoices->sortByDesc(fn (PaymentInvoice $invoice) => $invoice->paid_at ?? $invoice->updated_at)->take(5),
            'userCounts' => ['Admin Induk' => User::role(User::ROLE_ADMIN_INDUK)->count(), 'Admin Madrasah' => User::role(User::ROLE_ADMIN_SEKOLAH_MADRASAH)->count(), 'Guru/Pegawai' => User::role(User::ROLE_GURU_PEGAWAI)->count(), 'Semua Pengguna' => User::query()->count()],
            'employeeStatusCounts' => collect(Employee::employmentStatusOptions())->mapWithKeys(fn (string $label, string $key): array => [$label => (int) ($statusCounts[$key] ?? 0)]),
            'employeeTotal' => Employee::query()->where('is_active', true)->count(),
            'assignmentCounts' => $this->assignmentSummary(),
            'administrationCounts' => ['Pengajuan SK' => DecreeSubmission::query()->count(), 'Perbaikan SK' => DecreeCorrectionRequest::query()->count(), 'Menunggu Pemeriksaan' => DecreeSubmission::query()->where('status', DecreeSubmission::STATUS_UNDER_REVIEW)->count() + DecreeCorrectionRequest::query()->where('status', DecreeCorrectionRequest::STATUS_SUBMITTED)->count()],
            'decreeSubmissions' => $decreeSubmissions,
            'corrections' => $corrections,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function assignmentSummary(): array
    {
        return EmployeeAssignment::query()
            ->with(['employee:id,name', 'position:id,name', 'school:id,name'])
            ->where('status', EmployeeAssignment::STATUS_ACTIVE)
            ->whereNotNull('school_id')
            ->whereHas('employee', fn ($query) => $query->where('is_active', true))
            ->whereHas('position')
            ->whereHas('school')
            ->get()
            ->unique(fn (EmployeeAssignment $assignment): string => "{$assignment->school_id}:{$assignment->employee_position_id}:{$assignment->employee_id}")
            ->groupBy('employee_position_id')
            ->map(function ($assignments): array {
                $schools = $assignments->groupBy('school_id')
                    ->map(fn ($schoolAssignments): array => [
                        'name' => $schoolAssignments->first()->school->name,
                        'total' => $schoolAssignments->pluck('employee_id')->unique()->count(),
                        'employees' => $schoolAssignments->pluck('employee.name')->filter()->unique()->sort()->values()->all(),
                    ])
                    ->sortBy('name')
                    ->values()
                    ->all();

                return [
                    'position' => $assignments->first()->position->name,
                    'total' => $assignments->pluck('employee_id')->unique()->count(),
                    'schools' => $schools,
                ];
            })
            ->sortBy('position')
            ->values()
            ->all();
    }
}
