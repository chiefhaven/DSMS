<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MbiraSyncService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('mbirasync.base_url');
        $this->token   = config('mbirasync.token');
    }

    protected function client()
    {
        return Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
                ->acceptJson()
                ->baseUrl($this->baseUrl);
    }

    public function getAllPositions(): array
    {
        $response = $this->client()->get('/api/positions');

        if ($response->failed()) {
            Log::error('MbiraSync: failed to fetch positions', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return [];
        }

        return $response->json();
    }

    public function getPositionsByDevice(int $deviceId): array
    {
        $response = $this->client()->get('/api/positions', [
            'deviceId' => $deviceId,
        ]);

        if ($response->failed()) {
            Log::error("MbiraSync: failed to fetch positions for device {$deviceId}");
            return [];
        }

        return $response->json();
    }

    public function getPositionHistory(int $deviceId, string $from, string $to): array
    {
        $response = $this->client()->get('/api/reports/route', [
            'deviceId' => $deviceId,
            'from'     => $from,
            'to'       => $to,
        ]);

        if ($response->failed()) {
            Log::error("MbiraSync: failed to fetch history for device {$deviceId}");
            return [];
        }

        return $response->json();
    }

    public function getDevices(): array
    {
        $response = $this->client()->get('/api/devices');

        return $response->successful() ? $response->json() : [];
    }
}
