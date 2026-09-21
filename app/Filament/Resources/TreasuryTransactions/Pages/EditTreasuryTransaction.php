<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\TreasuryTransaction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditTreasuryTransaction extends EditRecord
{
    protected static string $resource = TreasuryTransactionResource::class;

    public function getTitle(): string
    {
        return $this->record->transaction_type === TreasuryTransaction::TYPE_EXPENSE
            ? 'Edit Pengeluaran'
            : 'Edit Pemasukan';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Hapus'),
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
