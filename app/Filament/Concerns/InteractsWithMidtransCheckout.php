<?php

namespace App\Filament\Concerns;

use App\Models\MidtransTransaction;
use App\Services\Payments\MidtransPaymentService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

trait InteractsWithMidtransCheckout
{
    public function refreshOnlinePayment(string $orderId, MidtransPaymentService $service): void
    {
        abort_unless(static::canAccess(), 403);
        $transaction = MidtransTransaction::query()->where('order_id', $orderId)
            ->whereIn('source', ['midtrans_sandbox', 'midtrans_production'])->firstOrFail();
        Gate::authorize('view', $transaction->invoice);
        try {
            $transaction = $service->synchronize($orderId);
            Notification::make()->title($transaction->transaction_status === 'paid'
                ? 'Pembayaran terverifikasi. Tagihan sudah lunas.'
                : 'Pembayaran belum terkonfirmasi lunas. Status akan diperbarui otomatis.')->info()->send();
        } catch (\Throwable) {
            Notification::make()->title('Status belum dapat diperiksa. Silakan cek kembali nanti.')->warning()->send();
        }
    }
}
