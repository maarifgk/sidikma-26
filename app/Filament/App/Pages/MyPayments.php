<?php

namespace App\Filament\App\Pages;

use App\Models\PaymentInvoice;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class MyPayments extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;
    protected static ?string $navigationLabel = 'Pembayaran Saya';
    protected static ?string $slug = 'pembayaran-saya';
    protected string $view = 'filament.app.pages.my-payments';
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
        $invoices = PaymentInvoice::query()
            ->where('user_id', $user?->getKey())
            ->latest()
            ->get();

        return [
            'user' => $user,
            'employee' => $user?->employee()->with('school')->first(),
            'invoices' => $invoices,
            'totalAmount' => $invoices->sum('amount'),
            'paidCount' => $invoices->where('status', PaymentInvoice::STATUS_PAID)->count(),
            'pendingCount' => $invoices->where('status', PaymentInvoice::STATUS_UNPAID)->count(),
        ];
    }
}
