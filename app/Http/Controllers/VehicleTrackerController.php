<?php

namespace App\Http\Controllers;

use App\Models\VehicleTracker;
use App\Http\Requests\StoreVehicleTrackerRequest;
use App\Http\Requests\UpdateVehicleTrackerRequest;
use App\Models\Fleet;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VehicleTrackerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreVehicleTrackerRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVehicleTrackerRequest $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = Auth::user();

        $instructor = $user->instructor;
        if (!$instructor) {
            abort(403, 'This user is not an instructor.');
        }

        $fleet = Fleet::where('instructor_id', $instructor->id)->first();
        if (!$fleet) {
            abort(404, 'Fleet not found for this instructor.');
        }

        $tracker = VehicleTracker::create([
            'fleet_id'  => $fleet->id,
            'user_id'   => $user->id,
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Vehicle location saved.',
            'data' => $tracker
        ], 201);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VehicleTracker  $vehicleTracker
     * @return \Illuminate\Http\Response
     */
    public function show(VehicleTracker $vehicleTracker)
    {
        $locations = VehicleTracker::select('fleet_id', 'latitude', 'longitude', DB::raw('MAX(created_at) as latest_time'))
        ->whereDate('created_at', Carbon::today())
        ->groupBy('fleet_id', 'latitude', 'longitude')
        ->get();

        return response()->json($locations);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\VehicleTracker  $vehicleTracker
     * @return \Illuminate\Http\Response
     */
    public function edit(VehicleTracker $vehicleTracker)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateVehicleTrackerRequest  $request
     * @param  \App\Models\VehicleTracker  $vehicleTracker
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVehicleTrackerRequest $request, VehicleTracker $vehicleTracker)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VehicleTracker  $vehicleTracker
     * @return \Illuminate\Http\Response
     */
    public function destroy(VehicleTracker $vehicleTracker)
    {
        //
    }
}
