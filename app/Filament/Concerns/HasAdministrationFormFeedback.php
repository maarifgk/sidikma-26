<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait HasAdministrationFormFeedback
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            panel: filament()->getCurrentPanel()?->getId(),
            isAbsolute: false,
        );
    }

    protected function getCreatedNotification(): ?Notification
    {
        return $this->successNotification('Data berhasil disimpan');
    }

    protected function getUpdatedNotification(): ?Notification
    {
        return $this->successNotification('Data berhasil diubah');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->icon('heroicon-o-x-circle')
            ->title('Gagal mengubah data')
            ->body('Periksa kembali kolom yang masih salah atau belum lengkap.')
            ->send();
    }

    private function successNotification(string $title): Notification
    {
        return Notification::make()
            ->success()
            ->icon('heroicon-o-check-circle')
            ->title($title);
    }
}
