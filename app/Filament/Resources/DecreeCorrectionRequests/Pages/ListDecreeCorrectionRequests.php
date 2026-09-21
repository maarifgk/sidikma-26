<?php

namespace App\Filament\Resources\DecreeCorrectionRequests\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\DecreeCorrectionRequests\DecreeCorrectionRequestResource;
use Filament\Actions\CreateAction;

class ListDecreeCorrectionRequests extends ListRecords
{
    protected static string $resource = DecreeCorrectionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Buat Pengajuan Perbaikan SK')->visible(fn () => auth()->user()?->hasRole('admin-sekolah-madrasah'))];
    }
}
