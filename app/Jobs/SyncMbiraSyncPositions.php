<?php

namespace App\Jobs;

use App\Models\FleetTracker;
use App\Services\MbiraSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMbiraSyncPositions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MbiraSyncService $mbiraSync): void
    {
        $positions = $mbiraSync->getAllPositions();

        if (empty($positions)) {
            Log::info('MbiraSync: no positions returned.');
            return;
        }

        foreach ($positions as $pos) {
            FleetTracker::updateOrCreate(
                ['mbirasync_id' => $pos['id']],
                [
                    'device_id'   => $pos['deviceId'],
                    'protocol'    => $pos['protocol'] ?? null,
                    'device_time' => $pos['deviceTime'] ?? null,
                    'fix_time'    => $pos['fixTime'] ?? null,
                    'latitude'    => $pos['latitude'],
                    'longitude'   => $pos['longitude'],
                    'altitude'    => $pos['altitude'] ?? 0,
                    'speed'       => $pos['speed'] ?? 0,
                    'course'      => $pos['course'] ?? 0,
                    'valid'       => $pos['valid'] ?? true,
                    'attributes'  => $pos['attributes'] ?? [],
                ]
            );
        }

        Log::info('MbiraSync: synced ' . count($positions) . ' positions.');
    }
}
