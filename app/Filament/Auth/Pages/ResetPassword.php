<?php

namespace App\Filament\Auth\Pages;

use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class ResetPassword extends BaseResetPassword
{
    protected static string $layout = 'filament.auth.layouts.sidikma';

    public function hasLogo(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Buat Password Baru SIDIKMA';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Buat Password Baru';
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Password Baru')
            ->placeholder('Masukkan password baru')
            ->prefixIcon('heroicon-o-lock-closed');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->label('Konfirmasi Password')
            ->placeholder('Ulangi password baru')
            ->prefixIcon('heroicon-o-lock-closed');
    }

    public function getResetPasswordFormAction(): Action
    {
        return parent::getResetPasswordFormAction()->label('Simpan Password Baru');
    }
}
