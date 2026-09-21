<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class MapProviderTileController extends Controller
{
    public function __invoke(string $provider, int $z, int $x, int $y): BinaryFileResponse|Response
    {
        abort_unless(in_array($provider, ['detail', 'satellite'], true), 404);
        abort_unless($z >= 0 && $z <= 19, 404);
        $maximumCoordinate = (2 ** $z) - 1;
        abort_unless($x >= 0 && $x <= $maximumCoordinate && $y >= 0 && $y <= $maximumCoordinate, 404);

        $path = public_path("map-provider-tiles/{$provider}/{$z}/{$x}/{$y}.png");
        if (! File::exists($path)) {
            $url = match ($provider) {
                'detail' => "https://a.basemaps.cartocdn.com/light_all/{$z}/{$x}/{$y}.png",
                'satellite' => "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{$z}/{$y}/{$x}",
            };

            try {
                $tile = Http::withHeaders([
                    'User-Agent' => config('app.name', 'Yayasan App').'/1.0 (attendance map)',
                    'Accept' => 'image/png,image/jpeg,image/*;q=0.8',
                ])->connectTimeout(5)->timeout(12)->get($url);
            } catch (ConnectionException) {
                return response('Tile peta tidak tersedia.', 503, ['Content-Type' => 'text/plain']);
            }

            if (! $tile->successful() || ! str_starts_with($tile->header('Content-Type'), 'image/')) {
                return response('Tile peta tidak tersedia.', 503, ['Content-Type' => 'text/plain']);
            }

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $tile->body(), true);
        }

        return response()->file($path, [
            'Content-Type' => $provider === 'satellite' ? 'image/jpeg' : 'image/png',
            'Cache-Control' => 'public, max-age=2592000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
