<?php

namespace App\Filament\App\Pages;

use App\Models\PaymentInvoice;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;

class MyProfile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;
    protected static ?string $navigationLabel = 'Profil Saya';
    protected static ?string $slug = 'profil-saya';
    protected string $view = 'filament.app.pages.my-profile';
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_GURU_PEGAWAI) ?? false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $employee = $user?->employee()->with(['school', 'currentAssignment.position'])->first();
        $avatarPath = $employee?->avatar_path ?: $user?->avatar_path;

        $avatarUrl = asset('images/default-avatar.svg');
        if (filled($avatarPath)) {
            $avatarUrl = str_starts_with((string) $avatarPath, 'http://')
                || str_starts_with((string) $avatarPath, 'https://')
                ? (string) $avatarPath
                : (Storage::disk('public')->exists((string) $avatarPath)
                    ? Storage::disk('public')->url((string) $avatarPath)
                    : $avatarUrl);
        }

        return [
            'user' => $user,
            'employee' => $employee,
            'avatarUrl' => $avatarUrl,
            'colleagueCount' => $employee?->school_id
                ? \App\Models\Employee::query()->where('school_id', $employee->school_id)->where('is_active', true)->count()
                : 0,
            'paidAmount' => PaymentInvoice::query()->where('user_id', $user?->getKey())->where('status', PaymentInvoice::STATUS_PAID)->sum('amount'),
            'invoiceCount' => PaymentInvoice::query()->where('user_id', $user?->getKey())->count(),
        ];
    }
}
