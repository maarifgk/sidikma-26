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
        if ($settings) {
            $settings->server_key = trim((string) $settings->server_key);
            $settings->client_key = trim((string) $settings->client_key);
        }
        if (! $settings || blank($settings->server_key) || blank($settings->client_key)) {
            throw ValidationException::withMessages(['payment' => 'Server key dan client key Midtrans belum diisi di Pengaturan Aplikasi.']);
        }
        $sandbox = str_starts_with($settings->server_key, 'SB-');
        if ($sandbox !== str_starts_with($settings->client_key, 'SB-')) {
            throw ValidationException::withMessages(['payment' => 'Server key dan client key berasal dari mode berbeda. Gunakan pasangan key dari mode yang sama.']);
        }
        if ($sandbox === (bool) $settings->midtrans_is_production) {
            throw ValidationException::withMessages(['payment' => $sandbox
                ? 'Key Sandbox terdeteksi, tetapi mode tersimpan Production. Pilih Sandbox lalu simpan Pengaturan Aplikasi.'
                : 'Key Production terdeteksi, tetapi mode tersimpan Sandbox. Pilih Production lalu simpan Pengaturan Aplikasi.']);
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
