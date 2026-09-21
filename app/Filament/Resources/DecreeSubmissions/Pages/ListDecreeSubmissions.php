<?php

namespace App\Filament\Resources\DecreeSubmissions\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use App\Services\DecreeSubmissionEligibility;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ListDecreeSubmissions extends ListRecords
{
    protected static string $resource = DecreeSubmissionResource::class;

    protected static ?string $title = 'PENGAJUAN SK';

    protected function getHeaderActions(): array
    {
        return [Action::make('create')->label('Buat Pengajuan')->icon('heroicon-o-plus')->action(function (): void {
            $schoolId = auth()->user()?->accessibleSchoolIds()->first();
            if (! $schoolId || ! app(DecreeSubmissionEligibility::class)->schoolCanSubmit($schoolId)) {
                Notification::make()->title('Pengajuan SK tidak tersedia')->body(app(DecreeSubmissionEligibility::class)->message())->danger()->send();

                return;
            }
            $this->redirect(DecreeSubmissionResource::getUrl('create', panel: filament()->getCurrentPanel()?->getId(), isAbsolute: false), navigate: true);
        })->visible(fn (): bool => DecreeSubmissionResource::canCreate())];
    }
}
