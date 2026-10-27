<?php

namespace App\Services\Payments;

use App\Models\MidtransTransaction;
use App\Models\PaymentInvoice;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MidtransPaymentService
{
    public function __construct(private readonly MidtransGateway $gateway) {}

    public function checkout(PaymentInvoice $invoice): array
    {
        Gate::authorize('view', $invoice);
        $settings = $this->gateway->settings();
        $source = $settings->midtrans_is_production ? 'midtrans_production' : 'midtrans_sandbox';
        $transaction = DB::transaction(function () use ($invoice, $source): MidtransTransaction {
            $invoice = PaymentInvoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            Gate::authorize('view', $invoice);
            if ($invoice->status !== PaymentInvoice::STATUS_UNPAID || (int) $invoice->getKey() === 837) {
                throw ValidationException::withMessages(['payment' => 'Tagihan ini tidak tersedia untuk pembayaran online.']);
            }
            if (! preg_match('/^[0-9]+(?:\.00)?$/', (string) $invoice->amount) || (float) $invoice->amount <= 0) {
                throw ValidationException::withMessages(['payment' => 'Nominal harus positif dalam rupiah bulat.']);
            }
            if (PaymentTransaction::query()->where('payment_invoice_id', $invoice->getKey())->where('status', PaymentTransaction::STATUS_PAID)->exists()) {
                throw ValidationException::withMessages(['payment' => 'Tagihan memiliki histori pembayaran sukses. Minta admin merekonsiliasi sisa tagihan sebelum membayar online.']);
            }
            $existing = MidtransTransaction::query()->where('payment_invoice_id', $invoice->getKey())
                ->whereIn('source', ['midtrans_sandbox', 'midtrans_production'])
                ->whereIn('transaction_status', ['pending', 'paid'])->first();
            if ($existing) {
                if ($existing->source !== $source || $existing->gross_amount !== $invoice->amount) {
                    throw ValidationException::withMessages(['payment' => 'Ada transaksi aktif dengan mode atau nominal berbeda. Selesaikan atau batalkan melalui Midtrans terlebih dahulu.']);
                }

                return $existing;
            }
            $orderId = 'SIDIKMA-'.Str::ulid();

            return MidtransTransaction::query()->create([
                'payment_invoice_id' => $invoice->getKey(),
                'user_id' => $invoice->user_id,
                'order_id' => $orderId,
                'idempotency_key' => $orderId,
                'gross_amount' => $invoice->amount,
                'transaction_status' => 'pending',
                'source' => $source,
            ]);
        });

        if (blank($transaction->snap_token)) {
            try {
                $response = $this->gateway->create([
                    'transaction_details' => ['order_id' => $transaction->order_id, 'gross_amount' => (int) $transaction->gross_amount],
                    'credit_card' => ['secure' => true],
                ]);
                if (empty($response['token']) || ! is_string($response['token'])) {
                    throw new \RuntimeException('Missing Snap token');
                }
                $transaction->update(['snap_token' => $response['token']]);
            } catch (Throwable) {
                // Retain the reserved order: a timeout may have reached Midtrans.
                throw ValidationException::withMessages(['payment' => 'Checkout Midtrans belum dapat dibuka. Coba kembali; jika tetap gagal, minta admin memeriksa order '.$transaction->order_id.'.']);
            }
        }

        return [
            'token' => $transaction->snap_token,
            'orderId' => $transaction->order_id,
            'clientKey' => $settings->client_key,
            'production' => $settings->midtrans_is_production,
        ];
    }

    public function notification(array $payload): void
    {
        $settings = $this->gateway->settings();
        $signature = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$settings->server_key);
        abort_unless(hash_equals($signature, $payload['signature_key']), 403);
        $this->synchronize($payload['order_id']);
    }

    public function synchronize(string $orderId): MidtransTransaction
    {
        $settings = $this->gateway->settings();
        $source = $settings->midtrans_is_production ? 'midtrans_production' : 'midtrans_sandbox';
        MidtransTransaction::query()->where('order_id', $orderId)->where('source', $source)->firstOrFail();
        // Only server-to-server status is trusted, never the browser's success callback.
        $status = $this->gateway->status($orderId);
        if (($status['order_id'] ?? null) !== $orderId || ($status['currency'] ?? 'IDR') !== 'IDR') {
            abort(422, 'Order atau mata uang tidak cocok.');
        }
        $state = match ($status['transaction_status'] ?? null) {
            'settlement' => 'paid',
            'capture' => ($status['fraud_status'] ?? null) === 'accept' ? 'paid' : 'pending',
            'pending' => 'pending',
            'deny', 'failure' => 'failed',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            'refund', 'partial_refund' => 'refunded',
            default => throw ValidationException::withMessages(['payment' => 'Status Midtrans belum didukung.']),
        };

        return DB::transaction(function () use ($orderId, $source, $status, $state): MidtransTransaction {
            $reference = MidtransTransaction::query()->where('order_id', $orderId)->firstOrFail();
            $invoice = PaymentInvoice::query()->lockForUpdate()->findOrFail($reference->payment_invoice_id);
            $transaction = MidtransTransaction::query()->where('order_id', $orderId)->where('source', $source)->lockForUpdate()->firstOrFail();
            $amount = (string) ($status['gross_amount'] ?? '');
            if (! preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/', $amount)
                || number_format((float) $amount, 2, '.', '') !== $transaction->gross_amount
                || $invoice->amount !== $transaction->gross_amount) {
                abort(422, 'Nominal transaksi tidak cocok.');
            }
            if (in_array($transaction->transaction_status, ['paid', 'refunded'], true) && $state !== 'refunded') {
                return $transaction;
            }
            if (in_array($transaction->transaction_status, ['failed', 'expired', 'cancelled'], true) && $state === 'pending') {
                return $transaction;
            }
            $transaction->update([
                'transaction_status' => $state,
                'transaction_id' => $status['transaction_id'] ?? $transaction->transaction_id,
                'payment_type' => $status['payment_type'] ?? null,
                'fraud_status' => $status['fraud_status'] ?? null,
                'settlement_time' => $state === 'paid' ? ($status['settlement_time'] ?? now()) : null,
                'raw_status_snapshot' => array_intersect_key($status, array_flip([
                    'order_id', 'transaction_id', 'transaction_status', 'status_code', 'gross_amount', 'payment_type', 'fraud_status',
                ])),
            ]);
            if ($state === 'paid') {
                $invoice->update(['status' => PaymentInvoice::STATUS_PAID, 'paid_at' => $transaction->settlement_time, 'payment_method' => 'online_gateway']);
                PaymentTransaction::query()->updateOrCreate(['midtrans_transaction_id' => $transaction->getKey()], [
                    'payment_invoice_id' => $invoice->getKey(),
                    'user_id' => $invoice->user_id,
                    'school_id' => $invoice->school_id,
                    'gateway_order_id' => $orderId,
                    'amount' => $transaction->gross_amount,
                    'payment_method' => 'online_gateway',
                    'status' => PaymentTransaction::STATUS_PAID,
                    'transacted_at' => $transaction->settlement_time,
                ]);
            }

            // Refunds remain visible for reconciliation; no automatic reopening of an invoice.
            return $transaction->refresh();
        });
    }
}
