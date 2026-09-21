<?php

namespace App\Filament\Resources\EmployeePositions\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\EmployeePositions\EmployeePositionResource;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;

class ListEmployeePositions extends ListRecords
{
    protected static string $resource = EmployeePositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('employees')
                ->label('Kembali ke Guru/Pegawai')
                ->icon('heroicon-o-arrow-left')
                ->url(EmployeeResource::getUrl(panel: 'admin', isAbsolute: false)),
            CreateAction::make(),
        ];
    }
}
