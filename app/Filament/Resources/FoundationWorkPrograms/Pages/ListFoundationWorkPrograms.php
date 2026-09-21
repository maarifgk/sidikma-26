<?php

namespace App\Filament\Resources\FoundationWorkPrograms\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\FoundationWorkPrograms\FoundationWorkProgramResource;
use App\Models\FoundationWorkProgram;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListFoundationWorkPrograms extends ListRecords
{
    protected static string $resource = FoundationWorkProgramResource::class;

    protected static ?string $title = 'PROGRAM KERJA';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.foundation-work-programs.summary')
                    ->viewData(fn (): array => $this->summaryData()),
                EmbeddedTable::make(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->icon('heroicon-o-plus'),
        ];
    }

    /** @return array<string, float|int> */
    private function summaryData(): array
    {
        $query = FoundationWorkProgramResource::getEloquentQuery();
        $total = (clone $query)->count();

        $percentage = static fn (int $count): float => $total > 0
            ? round(($count / $total) * 100, 1)
            : 0;

        return [
            'completedPercentage' => $percentage(
                (clone $query)->where('status', FoundationWorkProgram::STATUS_COMPLETED)->count(),
            ),
            'plannedPercentage' => $percentage(
                (clone $query)->where('status', FoundationWorkProgram::STATUS_PLANNED)->count(),
            ),
            'notImplementedPercentage' => $percentage(
                (clone $query)->where('status', FoundationWorkProgram::STATUS_NOT_IMPLEMENTED)->count(),
            ),
        ];
    }
}
