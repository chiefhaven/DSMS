<?php

namespace App\Http\Controllers;

use App\Models\FleetTracker;
use App\Http\Requests\StoreFleetTrackerRequest;
use App\Http\Requests\UpdateFleetTrackerRequest;
use App\Services\MbiraSyncService;
use Illuminate\Http\JsonResponse;

class FleetTrackerController extends Controller
{
    public function __construct(protected MbiraSyncService $mbiraSync) {}


    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $positions = FleetTracker::latest('fix_time')->get();
        return response()->json($positions);
    }

    public function live(): JsonResponse
    {
        $positions = $this->mbiraSync->getAllPositions();
        return response()->json($positions);
    }

    public function history(int $deviceId): JsonResponse
    {
        $from = request('from', now()->subDay()->toIso8601String());
        $to   = request('to',   now()->toIso8601String());

        $positions = $this->mbiraSync->getPositionHistory($deviceId, $from, $to);
        return response()->json($positions);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFleetTrackerRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(FleetTracker $fleetTracker)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FleetTracker $fleetTracker)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFleetTrackerRequest $request, FleetTracker $fleetTracker)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FleetTracker $fleetTracker)
    {
        //
    }
}
