<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Imports\EmployeeImporter;
use App\Filament\Pages\ListRecords;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static ?string $title = 'Guru/Pegawai';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->icon('heroicon-o-plus'),
            ImportAction::make()
                ->label('Import CSV')
                ->importer(EmployeeImporter::class)
                ->chunkSize(100)
                ->maxRows(2000)
                ->fileRules(['max:1024000'])
                ->visible(fn (): bool => auth()->user()?->isAdminInduk()
                    && auth()->user()->can('employee.create')),
        ];
    }
}
