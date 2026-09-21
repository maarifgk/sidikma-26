<?php

namespace App\Filament\Resources\SipinterUpdates\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\EditRecord;
use App\Filament\Resources\SipinterUpdates\SipinterUpdateResource;
use Filament\Actions\Action;

class EditSipinterUpdate extends EditRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = SipinterUpdateResource::class;

    protected static ?string $title = 'Update Data Sipinter';

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
