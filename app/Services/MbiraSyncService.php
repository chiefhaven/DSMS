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

    /**
     * Create a new device with Bearer token
     */
    public function createDevice(array $data)
    {
        try {
            // Prepare payload matching MbiraSync device structure
            $payload = [
                'id' => 0,
                'name' => $data['name'] ?? 'Unknown Vehicle',
                'uniqueId' => $data['unique_id'] ?? $data['uniqueId'] ?? '',
                'status' => $data['status'] ?? 'online',
                'disabled' => $data['disabled'] ?? false,
                'lastUpdate' => now()->toIso8601String(),
                'positionId' => $data['positionId'] ?? 0,
                'groupId' => 0,
                'phone' => $data['phone'] ?? '',
                'model' => $data['model'] ?? '',
                'contact' => $data['contact'] ?? '',
                'category' => $data['category'] ?? 'vehicle',
                'attributes' => $data['attributes'] ?? (object)[]
            ];

            Log::info('Sending payload to MbiraSync:', $payload);

            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/devices', $payload);

            if ($response->status() === 200) {
                $responseData = $response->json();
                Log::info('MbiraSync device created successfully', ['response' => $responseData]);
                return $responseData;
            }

            Log::error('MbiraSync API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('MbiraSync connection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing device in MbiraSync
     */
    public function updateDevice($deviceId, array $data)
    {
        try {
            $payload = [
                'id' => $deviceId,
                'name' => $data['name'] ?? '',
                'uniqueId' => $data['unique_id'] ?? $data['uniqueId'] ?? '',
                'status' => $data['status'] ?? 'online',
                'disabled' => $data['disabled'] ?? false,
                'groupId' => $data['group_id'] ?? $data['groupId'] ?? 1,
                'phone' => $data['phone'] ?? '',
                'model' => $data['model'] ?? '',
                'contact' => $data['contact'] ?? '',
                'category' => $data['category'] ?? 'vehicle',
                'attributes' => $data['attributes'] ?? (object)[]
            ];

            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->put($this->baseUrl . '/api/devices/' . $deviceId, $payload);

            return $response->successful() ? $response->json() : null;

        } catch (\Exception $e) {
            Log::error('Failed to update MbiraSync device', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all devices from MbiraSync
     */
    public function getAllDevices()
    {
        try {
            $response = Http::withToken($this->token)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($this->baseUrl . '/api/devices');

            if ($response->successful()) {
                return $response->json();
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Failed to fetch MbiraSync devices', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Delete device with Bearer token
     */
    public function deleteDevice($id)
    {
        try {
            $response = Http::withToken($this->token)
                ->withHeaders(['Accept' => 'application/json'])
                ->delete($this->baseUrl . '/api/devices/' . $id);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to delete MbiraSync device', ['error' => $e->getMessage()]);
            return false;
        }
    }


}
