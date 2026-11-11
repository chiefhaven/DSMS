<?php

namespace App\Http\Controllers;

use App\Models\VehicleTracker;
use App\Http\Requests\StoreVehicleTrackerRequest;
use App\Http\Requests\UpdateVehicleTrackerRequest;
use App\Models\Fleet;
use App\Models\FleetTracker;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
    public function show(FleetTracker $fleetTracker)
    {
        $locations = FleetTracker::select('device_id', 'latitude', 'longitude', 'speed', DB::raw('MAX(created_at) as latest_time'))
        ->whereDate('created_at', Carbon::today())
        ->groupBy('device_id', 'latitude', 'longitude', 'speed')->with('fleet')
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

    public function showVehicleGeoData(Request $request, $vehicleId)
    {
        $data = $request->validate([
            'period' => ['sometimes', Rule::in(['hour', 'today', 'yesterday', 'custom'])],
            'from' => 'required_if:period,custom|nullable|date_format:Y-m-d\TH:i',
            'to' => 'required_if:period,custom|nullable|date_format:Y-m-d\TH:i|after:from',
        ]);
        $now = Carbon::now(date_default_timezone_get());
        $period = $data['period'] ?? 'today';
        if ($period === 'custom') {
            $from = Carbon::createFromFormat('Y-m-d\TH:i', $data['from'], $now->timezone)->startOfMinute();
            $to = Carbon::createFromFormat('Y-m-d\TH:i', $data['to'], $now->timezone)->startOfMinute();
        } elseif ($period === 'hour') {
            $from = $now->copy()->subHour();
            $to = $now;
        } else {
            $from = $now->copy()->startOfDay();
            if ($period === 'yesterday') {
                $from->subDay();
            }
            $to = $from->copy()->addDay();
        }
        $fleet = Fleet::findOrFail($vehicleId);
        if (!$fleet->tracker_device_id) {
            return response()->json([]);
        }
        $dataPoints = FleetTracker::with('fleet')
            ->where('device_id', $fleet->tracker_device_id)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->orderBy('created_at', 'asc')
            ->get([
                'latitude',
                'longitude',
                'created_at'
            ]);

        return response()->json($dataPoints);

    }
}
