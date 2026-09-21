<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class MapTileController extends Controller
{
    public function __invoke(int $z, int $x, int $y): BinaryFileResponse|Response
    {
        abort_unless($z >= 0 && $z <= 19, 404);
        $maximumCoordinate = (2 ** $z) - 1;
        abort_unless($x >= 0 && $x <= $maximumCoordinate && $y >= 0 && $y <= $maximumCoordinate, 404);

        $directory = public_path("map-tiles/{$z}/{$x}");
        $path = "{$directory}/{$y}.png";

        if (! File::exists($path)) {
            $this->downloadNeighborhood($z, $x, $y, $maximumCoordinate);

            if (! File::exists($path)) {
                return response('Tile peta tidak tersedia.', 503, ['Content-Type' => 'text/plain']);
            }
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=2592000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function downloadNeighborhood(int $z, int $centerX, int $centerY, int $maximumCoordinate): void
    {
        $coordinates = [];

        for ($offsetX = -1; $offsetX <= 1; $offsetX++) {
            for ($offsetY = -1; $offsetY <= 1; $offsetY++) {
                $x = $centerX + $offsetX;
                $y = $centerY + $offsetY;

                if ($x < 0 || $x > $maximumCoordinate || $y < 0 || $y > $maximumCoordinate) {
                    continue;
                }

                $path = public_path("map-tiles/{$z}/{$x}/{$y}.png");
                if (! File::exists($path)) {
                    $coordinates["{$x}:{$y}"] = [$x, $y, $path];
                }
            }
        }

        if ($coordinates === []) {
            return;
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($coordinates, $z): array {
                $requests = [];

                foreach ($coordinates as $key => [$x, $y]) {
                    $requests[] = $pool->as($key)
                        ->withHeaders([
                            'User-Agent' => config('app.name', 'Yayasan App').'/1.0 (attendance map)',
                            'Accept' => 'image/png,image/*;q=0.8',
                        ])
                        ->connectTimeout(5)
                        ->timeout(12)
                        ->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");
                }

                return $requests;
            });
        } catch (ConnectionException) {
            return;
        }

        foreach ($responses as $key => $tile) {
            if (! $tile instanceof HttpResponse || ! $tile->successful() || ! str_starts_with($tile->header('Content-Type'), 'image/')) {
                continue;
            }

            $path = $coordinates[$key][2];
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $tile->body(), true);
        }
    }
}
