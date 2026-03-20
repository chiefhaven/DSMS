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
use DB;

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
        $trackerDeviceId = null;

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
                $trackerDeviceId = $trackingResult['id'] ?? null;

                // Log success
                Log::info('Tracking device created successfully', [
                    'device_id' => $post['tracker_device_id'],
                    'traccar_id' => $trackerDeviceId,
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
            $fleet->tracker_device_id = $trackerDeviceId;
            $fleet->tracker_unique_id = $post['tracker_device_id'];
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
        'reg_number.required' => 'The "car number plate" field is required!',
        'instructor.required' => 'The "instructor" field is required!',
        'licenceClass.required' => 'Licence class is required',
        'tracker_device_id.unique' => 'The tracker device ID is already in use.',
        'fleet_image.mimes' => 'The fleet image must be a file of type: jpeg, png, jpg, gif.',
        'car_description.max' => 'The car description may not exceed 1000 characters.',
    ];

    // Validate the request data
    $validatedData = $request->validate([
        'car_brand_model' => 'required|string|max:255',
        'reg_number' => 'required|string|max:50',
        'instructor' => 'required|exists:instructors,id', // Assuming instructor is a user ID
        'licenceClass' => 'required|exists:licence_classes,id',
        'tracker_device_id' => 'nullable|string|unique:fleets,tracker_device_id,' . $fleet->id . '|max:50',
        'fleet_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'car_description' => 'nullable|string|max:1000',
    ], $messages);

    $trackingResult = null;
    $trackerDeviceId = null;
    $message = null;

    // Use database transaction to ensure data consistency
    DB::beginTransaction();

    try {
        // Handle the car image upload if present
        if ($request->hasFile('fleet_image')) {
            $carImageName = time() . '-' . $request->file('fleet_image')->getClientOriginalName();
            $request->file('fleet_image')->move(public_path('media/fleet'), $carImageName);
            $fleet->fleet_image = $carImageName;
        }

        // Handle tracking device synchronization
        if (!empty($validatedData['tracker_device_id'])) {
            $trackingResult = $this->syncTrackingDevice(
                $fleet,
                $validatedData,
                $request
            );

            if (!$trackingResult['success']) {
                throw new \Exception($trackingResult['error']);
            }

            $trackerDeviceId = $trackingResult['traccar_id'];
        } elseif ($fleet->tracker_device_id) {
            // Optionally handle removal of tracking device if needed
            // $this->removeTrackingDevice($fleet);
        }

        // Handle instructor assignment logic
        $this->handleInstructorAssignment($fleet, $validatedData['instructor'], $message);

        // Update fleet details
        $fleet->car_brand_model = $validatedData['car_brand_model'];
        $fleet->car_registration_number = $validatedData['reg_number'];
        $fleet->licence_class_id = $validatedData['licenceClass'];
        $fleet->car_description = $validatedData['car_description'] ?? null;

        // Update tracking device information if available
        if ($trackingResult && $trackingResult['success']) {
            $fleet->tracker_device_id = $trackerDeviceId;
            $fleet->tracker_unique_id = $validatedData['tracker_device_id'];
        }

        // Save the updated fleet
        $fleet->save();

        DB::commit();

        // Redirect back with a success message
        return redirect()->route('fleet.index')
            ->with('message', $message ?? 'Fleet updated successfully!');

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Fleet update failed', [
            'fleet_id' => $fleet->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()
            ->withErrors(['error' => 'Failed to update fleet: ' . $e->getMessage()])
            ->withInput();
    }
}

/**
 * Sync tracking device with external service
 */
private function syncTrackingDevice(Fleet $fleet, array $data, Request $request): array
{
    try {
        $mbiraSyncService = new MbiraSyncService();

        // Prepare device data matching Traccar payload structure
        $deviceData = [
            'name' => $data['car_brand_model'] . ' - ' . $data['reg_number'],
            'unique_id' => $data['tracker_device_id'],
            'status' => 'active',
            'disabled' => false,
            'group_id' => $data['group_id'] ?? 1,
            'phone' => $data['contact_phone'] ?? '',
            'model' => $data['car_brand_model'],
            'contact' => '',
            'category' => 'vehicle',
            'attributes' => [
                'registration_number' => $data['reg_number'],
                'instructor_id' => $data['instructor'],
                'created_by' => Auth::user()->id,
                'created_at' => now()->toDateTimeString()
            ]
        ];

        if ($fleet->tracker_device_id) {
            // Update existing device
            $result = $mbiraSyncService->updateDevice($fleet->tracker_device_id, $deviceData);
            $traccarId = $fleet->tracker_device_id;
        } else {
            // Create new device
            $result = $mbiraSyncService->createDevice($deviceData);
            $traccarId = $result['id'] ?? null;
        }

        if (!$result) {
            return [
                'success' => false,
                'error' => 'Failed to sync tracking device'
            ];
        }

        Log::info('Tracking device synced successfully', [
            'fleet_id' => $fleet->id,
            'device_id' => $data['tracker_device_id'],
            'traccar_id' => $traccarId
        ]);

        return [
            'success' => true,
            'traccar_id' => $traccarId,
            'result' => $result
        ];

    } catch (\Exception $e) {
        Log::error('Tracking device sync failed', [
            'fleet_id' => $fleet->id,
            'error' => $e->getMessage(),
            'device_id' => $data['tracker_device_id']
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Handle instructor assignment logic
 */
private function handleInstructorAssignment(Fleet $fleet, string $instructorId, &$message): void
{
    // Check if the instructor is already assigned to this fleet
    if ($fleet->instructor_id === $instructorId) {
        return;
    }

    // Count how many fleets this instructor currently has
    $instructorFleetCount = Fleet::where('instructor_id', $instructorId)
        ->where('id', '!=', $fleet->id) // Exclude current fleet
        ->count();

    if ($instructorFleetCount === 0) {
        // No conflict, assign directly
        $fleet->instructor_id = $instructorId;
        $message = 'Instructor assigned successfully';
    } else {
        // Instructor is already assigned to other fleets
        // Option 1: Unassign from all other fleets
        Fleet::where('instructor_id', $instructorId)
            ->where('id', '!=', $fleet->id)
            ->update(['instructor_id' => null]);

        $fleet->instructor_id = $instructorId;
        $message = 'Instructor was reassigned from previous vehicles to ' . $fleet->car_brand_model;

        Log::info('Instructor reassigned', [
            'instructor_id' => $instructorId,
            'new_fleet_id' => $fleet->id,
            'previous_fleets_unassigned' => true
        ]);
    }
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
