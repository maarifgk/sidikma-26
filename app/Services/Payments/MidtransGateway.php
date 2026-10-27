<?php

namespace App\Services\Payments;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MidtransGateway
{
    public function settings(): ApplicationSetting
    {
        $settings = ApplicationSetting::query()->first();
        if (! $settings || blank($settings->server_key) || blank($settings->client_key)) {
            throw ValidationException::withMessages(['payment' => 'Server key dan client key Midtrans belum diisi di Pengaturan Aplikasi.']);
        }
        $sandbox = str_starts_with($settings->server_key, 'SB-');
        if ($sandbox === $settings->midtrans_is_production || $sandbox !== str_starts_with($settings->client_key, 'SB-')) {
            throw ValidationException::withMessages(['payment' => 'Mode Midtrans tidak sesuai dengan pasangan key. Periksa Pengaturan Aplikasi.']);
        }

        return $settings;
    }

    public function create(array $payload): array
    {
        $settings = $this->settings();
        $host = $settings->midtrans_is_production ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';

        return Http::withBasicAuth($settings->server_key, '')->acceptJson()->timeout(20)
            ->post($host.'/snap/v1/transactions', $payload)->throw()->json();
    }

    public function status(string $orderId): array
    {
        $settings = $this->settings();
        $host = $settings->midtrans_is_production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';

        return Http::withBasicAuth($settings->server_key, '')->acceptJson()->timeout(20)
            ->get($host.'/v2/'.rawurlencode($orderId).'/status')->throw()->json();
    }
}
