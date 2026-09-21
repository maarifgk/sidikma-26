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

        return [
            'user' => $user,
            'employee' => $employee,
            'avatarUrl' => filled($employee?->avatar_path)
                ? Storage::disk('public')->url($employee->avatar_path)
                : asset('images/default-avatar.svg'),
            'colleagueCount' => $employee?->school_id
                ? \App\Models\Employee::query()->where('school_id', $employee->school_id)->where('is_active', true)->count()
                : 0,
            'paidAmount' => PaymentInvoice::query()->where('user_id', $user?->getKey())->where('status', PaymentInvoice::STATUS_PAID)->sum('amount'),
            'invoiceCount' => PaymentInvoice::query()->where('user_id', $user?->getKey())->count(),
        ];
    }
}
