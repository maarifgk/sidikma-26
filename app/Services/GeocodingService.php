<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GeocodingService
{
    /** @return array<int, array{display_name: string, latitude: float, longitude: float, type: string}> */
    public function search(string $query): array
    {
        $query = Str::of($query)->squish()->limit(160, '')->toString();

        if (mb_strlen($query) < 3) {
            return [];
        }

        return Cache::remember('geocoding:nominatim:'.sha1(mb_strtolower($query)), now()->addHours(12), function () use ($query): array {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => config('app.name', 'Yayasan App').'/1.0 (attendance location search)',
                    'Accept-Language' => 'id,en;q=0.7',
                ])->connectTimeout(4)->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'countrycodes' => 'id',
                    'limit' => 7,
                ]);

                if (! $response->successful()) {
                    return [];
                }

                return collect($response->json())
                    ->filter(fn (mixed $item): bool => is_array($item) && isset($item['lat'], $item['lon'], $item['display_name']))
                    ->map(fn (array $item): array => [
                        'display_name' => (string) $item['display_name'],
                        'latitude' => round((float) $item['lat'], 7),
                        'longitude' => round((float) $item['lon'], 7),
                        'type' => (string) ($item['type'] ?? $item['category'] ?? 'lokasi'),
                    ])->values()->all();
            } catch (ConnectionException) {
                return [];
            }
        });
    }
}
