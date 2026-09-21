<?php

namespace Tests\Unit;

use App\Services\GeocodingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    public function test_it_normalizes_and_caches_nominatim_results(): void
    {
        Cache::flush();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([[
                'display_name' => 'MI Ma’arif Sawahan, Gunungkidul',
                'lat' => '-7.9653211',
                'lon' => '110.6032142',
                'type' => 'school',
            ]]),
        ]);

        $service = app(GeocodingService::class);
        $first = $service->search('MI Maarif Sawahan');
        $second = $service->search('MI Maarif Sawahan');

        $this->assertSame($first, $second);
        $this->assertSame(-7.9653211, $first[0]['latitude']);
        $this->assertSame(110.6032142, $first[0]['longitude']);
        Http::assertSentCount(1);
    }

    public function test_it_returns_empty_results_when_provider_is_unavailable(): void
    {
        Cache::flush();
        Http::fake(fn () => throw new ConnectionException('offline'));

        $this->assertSame([], app(GeocodingService::class)->search('Sekolah Tidak Tersedia'));
    }
}
