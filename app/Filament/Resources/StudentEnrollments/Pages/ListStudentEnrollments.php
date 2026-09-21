<?php

namespace App\Filament\Resources\StudentEnrollments\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class ListStudentEnrollments extends ListRecords
{
    protected static string $resource = StudentEnrollmentResource::class;

    protected static ?string $title = 'Data Jumlah Siswa per Tahun Pelajaran';

    #[Url(as: 'year')]
    public string $academicYear = '';

    public function mount(): void
    {
        parent::mount();

        if (blank($this->academicYear)) {
            $this->academicYear = StudentEnrollment::currentAcademicYear();
        }
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getHeading(): string|Htmlable|null
    {
        return view('filament.admin.resources.student-enrollments.heading');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.student-enrollments.summary')
                    ->viewData(fn (): array => [
                        'academicYear' => $this->academicYear,
                        'academicYearOptions' => StudentEnrollment::academicYearOptions(),
                        ...$this->summaryData(),
                    ]),
                EmbeddedTable::make(),
            ]);
    }

    public function updatedAcademicYear(): void
    {
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        return StudentEnrollmentResource::getEloquentQuery()
            ->where('academic_year', $this->academicYear);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Data')
                ->icon('heroicon-o-plus'),
        ];
    }

    /** @return array<string, int> */
    private function summaryData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [
                'totalStudents' => 0,
                'completedSchools' => 0,
                'incompleteSchools' => 0,
                'totalSchools' => 0,
            ];
        }

        $schoolQuery = School::query()
            ->accessibleTo($user)
            ->where('is_active', true);
        $enrollmentQuery = StudentEnrollmentResource::getEloquentQuery()
            ->where('academic_year', $this->academicYear);

        $totalSchools = $schoolQuery->count();
        $completedSchools = (clone $enrollmentQuery)->distinct()->count('school_id');

        return [
            'totalStudents' => (int) (clone $enrollmentQuery)->sum('total'),
            'completedSchools' => $completedSchools,
            'incompleteSchools' => max(0, $totalSchools - $completedSchools),
            'totalSchools' => $totalSchools,
        ];
    }
}
