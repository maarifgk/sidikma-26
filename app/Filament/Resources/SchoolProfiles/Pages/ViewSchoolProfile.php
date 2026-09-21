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
        $studentEnrollment = $school->studentEnrollments()
            ->orderByDesc('academic_year')
            ->first();
        $educatorRecap = $school->educatorRecaps()
            ->orderByDesc('academic_year')
            ->first();
        $activeEmployees = $school->employees()->where('is_active', true);

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
            'masterEmployeeTotal' => (clone $activeEmployees)->count(),
            'teacherTotal' => (clone $activeEmployees)->where('employee_type', 'guru')->count(),
            'staffTotal' => (clone $activeEmployees)->where('employee_type', 'pegawai')->count(),
            'gttTotal' => (clone $activeEmployees)->where('employment_status', 'GTT')->count(),
            'permanentFoundationStaffTotal' => (clone $activeEmployees)
                ->where('employment_status', 'Pegawai Tetap Yayasan')
                ->count(),
            'nonPermanentStaffTotal' => (clone $activeEmployees)
                ->where('employment_status', 'Pegawai Tidak Tetap')
                ->count(),
        ];
    }

    /** @return array<int, \stdClass> */
    private function positionSummaries(): array
    {
        /** @var School $school */
        $school = $this->getRecord();

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
