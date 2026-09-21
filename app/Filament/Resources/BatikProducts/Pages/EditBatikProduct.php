<?php

namespace App\Filament\Resources\BatikProducts\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\BatikOrders\BatikOrderResource;
use App\Filament\Resources\BatikProducts\BatikProductResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditBatikProduct extends EditRecord
{
    protected static string $resource = BatikProductResource::class;

    protected static ?string $title = 'Edit Produk Batik';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('hideFromCatalog')
                ->label('Hapus dari Tampilan')
                ->icon('heroicon-o-eye-slash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus produk dari tampilan katalog?')
                ->modalDescription('Produk tidak dihapus permanen. Riwayat pemesanan tetap tersimpan dengan aman.')
                ->modalSubmitActionLabel('Ya, hapus dari tampilan')
                ->action(function (): void {
                    $this->record->update(['is_active' => false]);

                    Notification::make()
                        ->success()
                        ->title('Produk dihapus dari tampilan katalog')
                        ->send();

                    $this->redirect($this->getRedirectUrl());
                }),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Simpan Perubahan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Kembali')
            ->url(BatikOrderResource::getUrl(panel: 'admin', isAbsolute: false));
    }

    protected function getRedirectUrl(): string
    {
        return BatikOrderResource::getUrl(panel: 'admin', isAbsolute: false);
    }
}
