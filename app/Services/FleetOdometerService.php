<?php

namespace App\Services;

use App\Models\Fleet;
use App\Models\FleetTracker;
use Carbon\Carbon;

class FleetOdometerService
{
    public function currentReading(Fleet $fleet): ?int
    {
        if ($fleet->odometer_km === null) {
            return null;
        }

        if (!$fleet->tracker_device_id || (!$fleet->odometer_reading_at && !$fleet->odometer_reading_date)) {
            return $fleet->odometer_km;
        }

        $from = $fleet->odometer_reading_at
            ?? $fleet->odometer_reading_date->copy()->addDay()->startOfDay();
        $to = Carbon::now();
        $previousPoint = null;
        $distanceKm = 0.0;

        foreach (FleetTracker::query()
            ->select(['latitude', 'longitude'])
            ->where('device_id', $fleet->tracker_device_id)
            ->where('valid', true)
            ->whereNotNull('fix_time')
            ->whereBetween('fix_time', [$from, $to])
            ->orderBy('fix_time')
            ->orderBy('id')
            ->cursor(['latitude', 'longitude']) as $point) {
            $currentPoint = [
                'latitude' => (float) $point->latitude,
                'longitude' => (float) $point->longitude,
            ];

            if (abs($currentPoint['latitude']) > 90 || abs($currentPoint['longitude']) > 180) {
                $previousPoint = null;
                continue;
            }

            if ($previousPoint !== null) {
                $distanceKm += $this->distanceBetween($previousPoint, $currentPoint);
            }

            $previousPoint = $currentPoint;
        }

        return (int) round($fleet->odometer_km + $distanceKm);
    }

    private function distanceBetween(array $first, array $second): float
    {
        $radians = static fn (float $degrees): float => $degrees * M_PI / 180;
        $latitudeDelta = $radians($second['latitude'] - $first['latitude']);
        $longitudeDelta = $radians($second['longitude'] - $first['longitude']);
        $value = sin($latitudeDelta / 2) ** 2
            + cos($radians($first['latitude'])) * cos($radians($second['latitude']))
            * sin($longitudeDelta / 2) ** 2;
        $value = min(1.0, max(0.0, $value));

        return 6371 * 2 * asin(sqrt($value));
    }
}
