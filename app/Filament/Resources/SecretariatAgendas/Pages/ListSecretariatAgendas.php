<?php

namespace App\Filament\Resources\SecretariatAgendas\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\SecretariatAgendas\SecretariatAgendaResource;
use Filament\Actions\CreateAction;

class ListSecretariatAgendas extends ListRecords
{
    protected static string $resource = SecretariatAgendaResource::class;

    protected static ?string $title = 'Agenda Kesekretariatan';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add'),
        ];
    }
}
