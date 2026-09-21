<?php

namespace App\Filament\Resources\PaymentTypes\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use Filament\Actions\CreateAction;

class ListPaymentTypes extends ListRecords
{
    protected static string $resource = PaymentTypeResource::class;

    protected static ?string $title = 'Pembayaran';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add'),
        ];
    }
}
