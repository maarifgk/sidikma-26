<?php

namespace App\Filament\Resources\Billings\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\Billings\BillingResource;
use App\Models\MidtransTransaction;
use App\Models\PaymentInvoice;
use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;

class EditBilling extends EditRecord
{
    protected static string $resource = BillingResource::class;

    protected static ?string $title = 'Edit Pembayaran';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $school = School::query()->findOrFail($data['school_id']);
        $target = User::query()->with('employee')->findOrFail($data['user_id']);

        $data['foundation_id'] = $school->foundation_id;
        $data['employee_id'] = $target->employee?->school_id === $school->getKey()
            ? $target->employee->getKey()
            : null;
        $data['paid_at'] = $data['status'] === PaymentInvoice::STATUS_PAID
            ? ($this->record->paid_at ?? now())
            : null;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Delete')
                ->requiresConfirmation()
                ->modalHeading('Hapus pembayaran dan histori transaksi?')
                ->modalDescription('Histori pembayaran dan transaksi Midtrans terkait akan ikut dihapus dan tidak dapat dipulihkan.')
                ->action(function (PaymentInvoice $record): void {
                    DB::transaction(function () use ($record): void {
                        PaymentTransaction::query()
                            ->where('payment_invoice_id', $record->getKey())
                            ->delete();
                        MidtransTransaction::query()
                            ->where('payment_invoice_id', $record->getKey())
                            ->delete();
                        $record->delete();
                    });
                    $this->redirect(BillingResource::getUrl('index', panel: 'admin'));
                }),
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
