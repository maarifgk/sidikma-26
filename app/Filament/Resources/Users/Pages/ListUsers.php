<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Admin';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->icon('heroicon-o-plus'),
        ];
    }
}
