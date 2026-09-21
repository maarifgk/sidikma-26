<?php

namespace App\Filament\Auth\Pages;

use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected static string $layout = 'filament.auth.layouts.sidikma';

    public function hasLogo(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Lupa Password SIDIKMA';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Lupa Password';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Masukkan email yang terdaftar pada SIDIKMA. Kami akan mengirimkan link untuk membuat password baru.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->placeholder('Masukkan email Anda')
            ->prefixIcon('heroicon-o-envelope')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus();
    }

    protected function getRequestFormAction(): Action
    {
        return parent::getRequestFormAction()->label('Kirim Link Reset Password');
    }

    protected function getFailureNotification(string $status): ?Notification
    {
        if ($status === Password::INVALID_USER) {
            return Notification::make()
                ->title('Jika email terdaftar, link reset password akan dikirim.')
                ->success();
        }

        return parent::getFailureNotification($status);
    }

    protected function getSentNotification(string $status): ?Notification
    {
        return Notification::make()
            ->title('Jika email terdaftar, link reset password akan dikirim.')
            ->success();
    }

    public function loginAction(): Action
    {
        return parent::loginAction()->label('Kembali ke Login');
    }
}
