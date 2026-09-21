<?php

namespace App\Filament\Resources\Foundations\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\Foundations\FoundationResource;
use Filament\Actions\CreateAction;

class ListFoundations extends ListRecords
{
    protected static string $resource = FoundationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
