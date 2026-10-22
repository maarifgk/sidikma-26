<?php

namespace App\Filament\Resources\SchoolProfiles\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\SchoolProfiles\SchoolProfileResource;
use App\Models\EducatorRecap;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\School;
use App\Models\StudentEnrollment;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class ViewSchoolProfile extends ViewRecord
{
    protected static string $resource = SchoolProfileResource::class;

    protected static ?string $title = 'Profile Madrasah/Sekolah';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.school-profiles.school-summary')
                    ->viewData(fn (): array => [
                        ...$this->summaryData(),
                        'positionSummaries' => $this->positionSummaries(),
                    ]),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    private function summaryData(): array
    {
        /** @var School $school */
        $school = $this->getRecord();
        // Student recap data is optional during staged deployments. Avoid
        // taking the entire profile page down when its migration has not run.
        $studentEnrollment = SchemaFacade::connection($school->getConnectionName())
            ->hasTable('student_enrollments')
            ? $school->studentEnrollments()
                ->orderByDesc('academic_year')
                ->first()
            : null;
        $connection = SchemaFacade::connection($school->getConnectionName());
        $hasEmployeeType = $connection->hasTable('employees') && $connection->hasColumn('employees', 'employee_type');
        $hasEmploymentStatus = $connection->hasTable('employees') && $connection->hasColumn('employees', 'employment_status');
        $educatorRecap = $connection->hasTable('educator_recaps')
            ? $school->educatorRecaps()->orderByDesc('academic_year')->first()
            : null;
        $activeEmployees = $connection->hasTable('employees')
            ? $school->employees()->where('is_active', true)
            : null;

        return [
            'school' => $school,
            'studentAcademicYear' => $studentEnrollment?->academic_year
                ?? StudentEnrollment::currentAcademicYear(),
            'studentTotal' => (int) ($studentEnrollment?->total ?? 0),
            'educatorAcademicYear' => $educatorRecap?->academic_year
                ?? EducatorRecap::currentAcademicYear(),
            'asnCertified' => (int) ($educatorRecap?->asn_certified ?? 0),
            'asnUncertified' => (int) ($educatorRecap?->asn_uncertified ?? 0),
            'foundationCertified' => (int) ($educatorRecap?->foundation_certified_inpassing ?? 0),
            'foundationUncertified' => (int) ($educatorRecap?->foundation_uncertified ?? 0),
            'educatorTotal' => (int) ($educatorRecap?->total ?? 0),
            'masterEmployeeTotal' => $activeEmployees ? (clone $activeEmployees)->count() : 0,
            'teacherTotal' => $activeEmployees && $hasEmployeeType ? (clone $activeEmployees)->where('employee_type', 'guru')->count() : 0,
            'staffTotal' => $activeEmployees && $hasEmployeeType ? (clone $activeEmployees)->where('employee_type', 'pegawai')->count() : 0,
            'gttTotal' => $activeEmployees && $hasEmploymentStatus ? (clone $activeEmployees)->where('employment_status', 'GTT')->count() : 0,
            'permanentFoundationStaffTotal' => $activeEmployees && $hasEmploymentStatus ? (clone $activeEmployees)
                ->where('employment_status', 'Pegawai Tetap Yayasan')
                ->count() : 0,
            'nonPermanentStaffTotal' => $activeEmployees && $hasEmploymentStatus ? (clone $activeEmployees)
                ->where('employment_status', 'Pegawai Tidak Tetap')
                ->count() : 0,
        ];
    }

    /** @return array<int, \stdClass> */
    private function positionSummaries(): array
    {
        /** @var School $school */
        $school = $this->getRecord();

        $connection = SchemaFacade::connection($school->getConnectionName());
        if (! $connection->hasTable('employee_positions') || ! $connection->hasTable('employee_assignments')) {
            return [];
        }

        return EmployeePosition::query()
            ->whereHas('assignments', fn ($query) => $query
                ->where('school_id', $school->getKey())
                ->where('status', EmployeeAssignment::STATUS_ACTIVE))
            ->withCount([
                'assignments as active_assignments_count' => fn ($query) => $query
                    ->where('school_id', $school->getKey())
                    ->where('status', EmployeeAssignment::STATUS_ACTIVE),
            ])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (EmployeePosition $position): object => (object) [
                'name' => $position->name,
                'total' => (int) $position->active_assignments_count,
            ])
            ->all();
    }
}
