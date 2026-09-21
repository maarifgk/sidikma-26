<?php

namespace App\Filament\Resources\EducatorRecaps\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\EducatorRecaps\EducatorRecapResource;
use App\Models\EducatorRecap;
use App\Models\School;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class ListEducatorRecaps extends ListRecords
{
    protected static string $resource = EducatorRecapResource::class;

    protected static ?string $title = 'Data Jumlah Tenaga Pendidik per Tahun Pelajaran';

    #[Url(as: 'year')]
    public string $academicYear = '';

    public function mount(): void
    {
        parent::mount();

        if (blank($this->academicYear)) {
            $this->academicYear = EducatorRecap::currentAcademicYear();
        }
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getHeading(): string|Htmlable|null
    {
        return view('filament.admin.resources.educator-recaps.heading');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.educator-recaps.summary')
                    ->viewData(fn (): array => [
                        'academicYear' => $this->academicYear,
                        'academicYearOptions' => EducatorRecap::academicYearOptions(),
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
        return EducatorRecapResource::getEloquentQuery()
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
                'totalEducators' => 0,
                'completedSchools' => 0,
                'incompleteSchools' => 0,
                'totalSchools' => 0,
            ];
        }

        $schoolQuery = School::query()
            ->accessibleTo($user)
            ->where('is_active', true);
        $recapQuery = EducatorRecapResource::getEloquentQuery()
            ->where('academic_year', $this->academicYear);

        $totalSchools = $schoolQuery->count();
        $completedSchools = (clone $recapQuery)->distinct()->count('school_id');

        return [
            'totalEducators' => (int) (clone $recapQuery)->sum('total'),
            'completedSchools' => $completedSchools,
            'incompleteSchools' => max(0, $totalSchools - $completedSchools),
            'totalSchools' => $totalSchools,
        ];
    }
}
