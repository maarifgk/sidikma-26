<?php

namespace App\Filament\App\Pages;

use App\Filament\Resources\EmployeeActivityRequests\EmployeeActivityRequestResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeActivityRequest;
use App\Models\EmployeeAssignment;
use App\Models\PaymentInvoice;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\DashboardMetrics;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.app.pages.school-dashboard')
                ->viewData(fn (): array => $this->dashboardData()),
        ]);
    }

    /** @return array<string, mixed> */
    private function dashboardData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $metrics = app(DashboardMetrics::class);
        $schoolIds = $user->accessibleSchoolIds();
        $school = $metrics->schools($user)
            ->where('is_active', true)
            ->orderBy('name')
            ->first();
        $academicYear = StudentEnrollment::currentAcademicYear();
        $enrollments = StudentEnrollment::query()
            ->whereIn('school_id', $schoolIds)
            ->where('academic_year', $academicYear)
            ->get();
        $grades = collect(StudentEnrollment::GRADE_FIELDS)
            ->mapWithKeys(fn (string $grade): array => [
                strtoupper($grade) => $enrollments->sum(fn (StudentEnrollment $row): int => (int) $row->{$grade}),
            ]);
        $totalStudents = (int) $grades->sum();
        $activeClasses = $grades->filter(fn (int $total): bool => $total > 0)->count();
        $largestClass = max(1, (int) $grades->max());
        $employeeQuery = $metrics->employees($user)->where('is_active', true);
        $employeeCount = (clone $employeeQuery)->count();
        $employees = (clone $employeeQuery)
            ->with(['currentAssignment.position'])
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Employee $employee): array => [
                'name' => $employee->name,
                'avatarUrl' => filled($employee->avatar_path)
                    ? Storage::disk('public')->url($employee->avatar_path)
                    : asset('images/default-avatar.svg'),
                'position' => $employee->currentAssignment?->position?->name ?? 'Tenaga Pendidik',
                'status' => $this->employmentStatus($employee->employment_status),
                'url' => EmployeeResource::getUrl(
                    'view',
                    ['record' => $employee],
                    panel: 'app',
                    isAbsolute: false,
                ),
            ]);
        $activities = EmployeeActivityRequest::query()
            ->whereIn('school_id', $schoolIds)
            ->with('school')
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (EmployeeActivityRequest $activity): array => [
                'name' => $activity->employee_name,
                'school' => $activity->school?->name ?? $activity->school_name,
                'status' => EmployeeActivityRequest::statusOptions()[$activity->status] ?? $activity->status,
                'createdAt' => $activity->created_at?->locale('id')->translatedFormat('d M Y H:i'),
            ]);
        $currentEmployee = Employee::query()
            ->with(['school', 'currentAssignment.position'])
            ->where('user_id', $user->getKey())
            ->first();
        $invoices = PaymentInvoice::query()->where('user_id', $user->getKey())->get();

        return [
            'school' => $school,
            'academicYear' => $academicYear,
            'totalStudents' => $totalStudents,
            'activeClasses' => $activeClasses,
            'averageStudents' => $activeClasses > 0 ? (int) round($totalStudents / $activeClasses) : 0,
            'grades' => $grades->map(fn (int $total, string $grade): array => [
                'label' => 'Kelas '.ltrim($grade, 'K'),
                'total' => $total,
                'percentage' => $total > 0 ? (int) round(($total / $largestClass) * 100) : 0,
            ])->values(),
            'employeeCount' => $employeeCount,
            'assignmentCounts' => $this->assignmentSummary($schoolIds->all()),
            'accountCount' => $metrics->users($user)->where('is_active', true)->count(),
            'employees' => $employees,
            'activities' => $activities,
            'schoolEditUrl' => $school && $user->can('update', $school)
                ? SchoolResource::getUrl(
                    'edit',
                    ['record' => $school],
                    panel: 'app',
                    isAbsolute: false,
                )
                : null,
            'activitiesUrl' => EmployeeActivityRequestResource::getUrl(panel: 'app', isAbsolute: false),
            'currentEmployee' => $currentEmployee,
            'mobileAvatarUrl' => filled($currentEmployee?->avatar_path)
                ? Storage::disk('public')->url($currentEmployee->avatar_path)
                : asset('images/default-avatar.svg'),
            'paidAmount' => $invoices->where('status', PaymentInvoice::STATUS_PAID)->sum('amount'),
            'paidInvoiceCount' => $invoices->where('status', PaymentInvoice::STATUS_PAID)->count(),
            'documentCount' => $currentEmployee
                ? Document::query()->whereMorphedTo('owner', $currentEmployee)->where('status', Document::STATUS_ACTIVE)->count()
                : 0,
        ];
    }

    /** @param array<int, int> $schoolIds
     * @return array<int, array<string, mixed>>
     */
    private function assignmentSummary(array $schoolIds): array
    {
        return EmployeeAssignment::query()
            ->with(['employee:id,name', 'position:id,name', 'school:id,name'])
            ->whereIn('school_id', $schoolIds)
            ->where('status', EmployeeAssignment::STATUS_ACTIVE)
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

    private function employmentStatus(?string $status): string
    {
        return $status ? Employee::employmentStatusLabel($status) : 'Status belum diisi';
    }
}
