<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\Instructor;
use App\Models\Student;
use App\Http\Requests\StoreFleetRequest;
use App\Http\Requests\UpdateFleetRequest;
use App\Models\licenceClass;
use RealRashid\SweetAlert\Facades\Alert;
use App\Services\MbiraSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Auth;

class FleetController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:superAdmin|admin']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $fleet = Fleet::with('Instructor', 'licenceClass')->get();

        $instructors = Instructor::whereHas('department', function($query) {
            $query->where('name', 'Practical');
        })->get();

        $licenceClasses = licenceClass::get();

        $student = Student::get();
        return view('fleet.fleet', compact('fleet', 'instructors', 'student', 'licenceClasses'));
    }

    public function getFleet(Request $request)
    {
        $class = $request->query('studentCourseClass');

        if (!$class) {
            return response()->json([], 200);
        }

        $fleets = Fleet::with('Instructor', 'licenceClass')
            ->where('licence_class_id', $class)
            ->get();

        return response()->json($fleets, 200);
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
     * @param  \App\Http\Requests\StoreFleetRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFleetRequest $request)
{
    $messages = [
        'car_brand_model.required' => 'The "car name/brand" field is required!',
        'reg_number.required' => 'The "car number plate" field is required!',
        'reg_number.unique' => 'This car number plate is already registered!',
        'licenceClass.required' => 'Licence class is required',
    ];

    // Validate the request
    $this->validate($request, [
        'car_brand_model' => 'required|string|max:255',
        'licenceClass' => 'required|exists:licence_classes,id',
        'reg_number' => 'required|string|unique:fleets,car_registration_number|max:20',
        'tracker_device_id' => 'nullable|string|unique:fleets,tracker_device_id|max:50',
        'instructor' => 'required|exists:instructors,id',
        'fleet_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'car_description' => 'nullable|string|max:1000',
        'group_id' => 'nullable|integer'
    ], $messages);

    $post = $request->all();
    $trackingResult = null;
    $traccarDeviceId = null;

    // Handle tracking device creation if tracker_device_id is provided
    if(isset($post['tracker_device_id']) && !empty($post['tracker_device_id'])){
        try {
            // Initialize the service
            $mbiraSyncService = new MbiraSyncService();

            // Prepare device data matching Traccar payload structure
            $deviceData = [
                'name' => $post['car_brand_model'] . ' - ' . $post['reg_number'],
                'unique_id' => $post['tracker_device_id'], // This maps to uniqueId
                'status' => 'active',
                'disabled' => false,
                'group_id' => $post['group_id'] ?? 1,
                'phone' => $post['contact_phone'] ?? '',
                'model' => $post['car_brand_model'],
                'contact' => '',
                'category' => 'vehicle',
                'attributes' => [
                    'registration_number' => $post['reg_number'],
                    'instructor_id' => $post['instructor'],
                    'created_by' => Auth::user()->id,
                    'created_at' => now()->toDateTimeString()
                ]
            ];

            // Call createDevice with the prepared data
            $trackingResult = $mbiraSyncService->createDevice($deviceData);

            // Check if device creation failed
            if (!$trackingResult) {
                return redirect()->back()
                    ->withErrors(['tracker_device_id' => 'Failed to create tracking device. Please check if the device ID is valid.'])
                    ->withInput();
            }

            // Extract the Traccar device ID from the response
            $traccarDeviceId = $trackingResult['id'] ?? null;

            // Log success
            Log::info('Tracking device created successfully', [
                'device_id' => $post['tracker_device_id'],
                'traccar_id' => $traccarDeviceId,
                'response' => $trackingResult
            ]);

        } catch (\Exception $e) {
            Log::error('Tracking device creation failed', [
                'error' => $e->getMessage(),
                'device_id' => $post['tracker_device_id']
            ]);

            return redirect()->back()
                ->withErrors(['tracker_device_id' => 'Error creating tracking device: ' . $e->getMessage()])
                ->withInput();
        }
    }

    // Create new fleet record
    $fleet = new Fleet();

    // Car image processing
    if($request->file('fleet_image')){
        $carImageName = time() . '-' . $request->file('fleet_image')->getClientOriginalName();
        $request->fleet_image->move(public_path('media/fleet'), $carImageName);
        $fleet->fleet_image = $carImageName;
    } else {
        $fleet->fleet_image = 'driving-school-car-default.png';
    }

    // Get instructor
    $instructor = Instructor::findOrFail($post['instructor']);

    // Check instructor status
    if ($instructor->status === 'Suspended' || $instructor->status == 0) {
        return redirect()->back()
            ->withErrors(['instructor' => 'The selected instructor is suspended or unavailable.'])
            ->withInput();
    }

    // Assign all fleet data
    $fleet->instructor_id = $instructor->id;
    $fleet->car_brand_model = $post['car_brand_model'];
    $fleet->licence_class_id = $post['licenceClass'];
    $fleet->car_registration_number = $post['reg_number'];
    $fleet->car_description = $post['car_description'] ?? '';

    // Store tracking device information if available
    if ($trackingResult) {
        $fleet->tracker_device_id = $traccarDeviceId;
        $fleet->tracker_unique_id = $post['tracker_device_id'];
        // $fleet->traccar_device_id = $traccarDeviceId;
        // $fleet->tracking_device_data = json_encode($trackingResult);
        // $fleet->has_tracking = true;
        // $fleet->tracking_status = $trackingResult['status'] ?? 'active';
        // $fleet->last_tracking_update = now();
    }

    // Save the fleet record
    $fleet->save();

    // Prepare success message
    $message = 'Car added to fleet successfully!';

    return redirect()->back()->with('message', $message);
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $fleet = Fleet::with('instructor')->findOrFail($id);

        return view('fleet.viewfleet', compact('fleet'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $fleet = Fleet::with('Instructor')->find($id);

        $instructors = Instructor::whereHas('department', function($query) {
            $query->where('name', 'Practical');
        })->get();

        $licenceClasses = licenceClass::get();

        return view('fleet.editfleet', [ 'fleet' => $fleet ], compact('fleet', 'instructors', 'licenceClasses'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFleetRequest  $request
     * @param  \App\Models\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFleetRequest $request, Fleet $fleet)
    {
        // Custom validation messages
        $messages = [
            'car_brand_model.required' => 'The "car name/brand" field is required!',
            'reg_number.required' => 'The "car number plate" field should be unique!',
            'instructor.required' => 'The "instructor" field should be unique!',
            'licenceClass.required' => 'Licence class is required',
        ];

        // Validate the request data
        $request->validate([
            'car_brand_model' => 'required',
            'reg_number' => 'required',
            'instructor' => 'required',
            'licenceClass' =>'required',
        ], $messages);

        // Get the validated data
        $post = $request->all();

        // Find the fleet to update
        $fleet = Fleet::find($post['id']);
        $fleetCount = Fleet::where('instructor_id', $post['instructor'])->count();

        // Handle the car image upload if present
        if ($request->hasFile('fleet_image')) {
            // Use Laravel's storage system to handle the file upload
            $carImageName = time() . '-' . $request->file('fleet_image')->getClientOriginalName();
            $request->file('fleet_image')->move(public_path('media/fleet'), $carImageName);
            $fleet->fleet_image = $carImageName;
        }

        if ($fleetCount < 1 ) {
            $fleet->instructor_id = $post['instructor'] ?? 1000000;
        }
        elseif ($fleetCount > 0) {
            // Unassign the instructor from all other fleets
            $fleets = Fleet::where('instructor_id', $post['instructor'])->get();
            foreach ($fleets as $fleet_1) {
                $fleet_1->instructor_id = null;
                $fleet_1->save(); // Save the unassigned fleet
            }

            $fleet->instructor_id = $post['instructor'];

            $message = 'Instructor was assigned to different car, has been unassigned and reassigned to '.$fleet->car_brand_model;
        }
        else {
            $fleet->instructor_id = null;
            $message = 'Instructor has been unassigned';
        }

        // Update the fleet details
        $fleet->car_brand_model = $post['car_brand_model'];
        $fleet->car_registration_number = $post['reg_number'];
        $fleet->licence_class_id = $post['licenceClass'];
        $fleet->car_description = $post['car_description'];

        // Save the updated fleet
        $fleet->save();

        // Redirect back with a success message
        return redirect()->route('fleet.index')->with('message', $message ?? 'Fleet updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Check if any students are assigned to this fleet
        $studentsCount = Student::where('fleet_id', $id)->where('status', '!=', 'Finished')->count();

        if ($studentsCount > 0) {
            $message = "Cannot delete fleet as it still has active students assigned to it!";
            Alert::toast($message, 'error');
            return redirect()->back()->with('message', $message);
        }

        // Find the fleet
        $fleet = Fleet::find($id);

        if (!$fleet) {
            $message = "Fleet not found";
            Alert::toast($message, 'error');
            return redirect()->back()->with('message', $message);
        }

        // Delete the fleet
        $fleet->delete();

        $message = "Fleet deleted successfully";
        Alert::toast($message, 'success');

        return redirect()->back()->with('message', $message);
    }

}
