<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\Foundation;
use App\Models\TreasuryTransaction;
use Filament\Actions\Action;
use Livewire\Attributes\Url;

class CreateTreasuryTransaction extends CreateRecord
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected static bool $canCreateAnother = false;

    #[Url(as: 'type')]
    public string $transactionType = TreasuryTransaction::TYPE_INCOME;

    public function mount(): void
    {
        if (! in_array($this->transactionType, [TreasuryTransaction::TYPE_INCOME, TreasuryTransaction::TYPE_EXPENSE], true)) {
            $this->transactionType = TreasuryTransaction::TYPE_INCOME;
        }

        parent::mount();
    }

    public function getTitle(): string
    {
        return $this->transactionType === TreasuryTransaction::TYPE_EXPENSE
            ? 'Tambah Data Pengeluaran'
            : 'Tambah Data Pemasukan';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Foundation::application()->getKey();
        $data['transaction_type'] = $this->transactionType;

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->color('gray');
    }
}
