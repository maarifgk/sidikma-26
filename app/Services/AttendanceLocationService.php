<?php

namespace App\Services;

class AttendanceLocationService
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function calculateDistance(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $value = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * atan2(sqrt($value), sqrt(1 - $value));
    }

    public function isInsideRadius(float $distanceMeters, int $radiusMeters): bool
    {
        return $distanceMeters <= $radiusMeters;
    }

    public function hasAcceptableAccuracy(float $accuracyMeters, float|int|null $maximumAccuracyMeters = null): bool
    {
        return $accuracyMeters <= (float) ($maximumAccuracyMeters ?? config('attendance.maximum_accuracy_meters', 100));
    }

    /** @param array<int, array{latitude: float|int|string, longitude: float|int|string}> $polygon */
    public function isInsidePolygon(float $latitude, float $longitude, array $polygon): bool
    {
        if (count($polygon) < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = count($polygon) - 1; $i < count($polygon); $j = $i++) {
            $yi = (float) $polygon[$i]['latitude'];
            $xi = (float) $polygon[$i]['longitude'];
            $yj = (float) $polygon[$j]['latitude'];
            $xj = (float) $polygon[$j]['longitude'];
            $intersects = (($yi > $latitude) !== ($yj > $latitude))
                && ($longitude < (($xj - $xi) * ($latitude - $yi) / ($yj - $yi)) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
