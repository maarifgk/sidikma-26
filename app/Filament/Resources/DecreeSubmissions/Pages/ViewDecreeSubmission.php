<?php

namespace App\Filament\Resources\DecreeSubmissions\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use Filament\Actions\EditAction;

class ViewDecreeSubmission extends ViewRecord
{
    protected static string $resource = DecreeSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false)];
    }
}
