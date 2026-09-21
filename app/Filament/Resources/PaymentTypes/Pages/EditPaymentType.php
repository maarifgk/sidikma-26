<?php

namespace App\Filament\Resources\PaymentTypes\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditPaymentType extends EditRecord
{
    protected static string $resource = PaymentTypeResource::class;

    protected static ?string $title = 'Edit Pembayaran';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Delete'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Simpan Perubahan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
