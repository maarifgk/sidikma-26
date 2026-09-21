<?php

namespace App\Filament\Resources\PaymentTypes\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use App\Models\Foundation;
use Filament\Actions\Action;

class CreatePaymentType extends CreateRecord
{
    protected static string $resource = PaymentTypeResource::class;

    protected static ?string $title = 'Tambah Pembayaran';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Foundation::application()->getKey();

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
