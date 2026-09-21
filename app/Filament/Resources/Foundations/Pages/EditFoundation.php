<?php

namespace App\Filament\Resources\Foundations\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\Foundations\FoundationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;

class EditFoundation extends EditRecord
{
    protected static string $resource = FoundationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
