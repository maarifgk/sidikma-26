<?php

namespace App\Filament\Auth\Pages;

use App\Models\User;
use App\Services\SchoolAdminMembershipService;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class IntegratedLogin extends Login
{
    protected static string $layout = 'filament.auth.layouts.sidikma';

    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if (! $response instanceof LoginResponse) {
            return null;
        }

        $user = Filament::auth()->user();

        if ($user instanceof User) {
            app(SchoolAdminMembershipService::class)->sync($user);

            if ((Filament::getCurrentOrDefaultPanel()->getId() === 'app') && $user->isAdminInduk()) {
                session()->put('url.intended', url('/admin'));
            } elseif ((Filament::getCurrentOrDefaultPanel()->getId() === 'admin') && (! $user->isAdminInduk())) {
                session()->put('url.intended', url('/app'));
            }
        }

        return $response;
    }

    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        if (
            ($user instanceof User)
            && (Filament::getCurrentOrDefaultPanel()->getId() === 'admin')
            && $user->canAccessPanel(Filament::getPanel('app'))
        ) {
            return true;
        }

        return parent::isUserAllowedToAccessPanel($user);
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Login SIDIKMA';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Masuk ke akun Anda';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Silakan gunakan email dan password yang terdaftar untuk melanjutkan ke dashboard.';
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

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Password')
            ->placeholder('Masukkan password')
            ->hint(new HtmlString('<a class="fi-link" href="'.e(filament()->getRequestPasswordResetUrl()).'">Lupa password?</a>'))
            ->prefixIcon('heroicon-o-lock-closed');
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()->label('Ingat saya');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()->label('Masuk ke Dashboard');
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => 'Email atau password yang Anda masukkan tidak sesuai.',
        ]);
    }
}
