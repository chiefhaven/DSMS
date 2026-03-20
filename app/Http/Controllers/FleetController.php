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
    // The StoreFleetRequest already handles validation
    $validatedData = $request->validated();

    $trackingResult = null;
    $trackerDeviceId = null;
    $trackerUniqueId = null;
    $message = null;
    $previousVehicle = null;

    // Use database transaction to ensure data consistency
    DB::beginTransaction();

    try {
        // Check instructor status and availability
        $instructor = Instructor::findOrFail($validatedData['instructor']);

        if ($instructor->status === 'Suspended' || $instructor->status == 0) {
            throw new \Exception('The selected instructor is suspended or unavailable.');
        }

        // Check if instructor is already assigned to another fleet
        $existingFleet = Fleet::where('instructor_id', $instructor->id)
            ->first();

        // Store previous vehicle info for messaging
        if ($existingFleet) {
            $previousVehicle = [
                'brand_model' => $existingFleet->car_brand_model,
                'registration' => $existingFleet->car_registration_number
            ];
        }

        // Handle tracking device creation if tracker_device_id is provided
        if (!empty($validatedData['tracker_device_id'])) {
            $trackingResult = $this->createTrackingDevice($validatedData, $instructor);

            if (!$trackingResult['success']) {
                throw new \Exception($trackingResult['error']);
            }

            $trackerDeviceId = $trackingResult['traccar_id'];
            $trackerUniqueId = $validatedData['tracker_device_id'];
        }

        // Create new fleet record
        $fleet = new Fleet();

        // Handle car image upload
        $fleet->fleet_image = $this->handleImageUpload($request);

        // Assign basic fleet data
        $fleet->car_brand_model = $validatedData['car_brand_model'];
        $fleet->licence_class_id = $validatedData['licenceClass'];
        $fleet->car_registration_number = $validatedData['reg_number'];
        $fleet->car_description = $validatedData['car_description'] ?? '';

        // Store tracking device information if available
        if ($trackingResult && $trackingResult['success']) {
            $fleet->tracker_device_id = $trackerDeviceId;
            $fleet->tracker_unique_id = $trackerUniqueId;
        }

        // Handle instructor assignment with reassignment
        $this->handleInstructorAssignmentForStore($fleet, $instructor->id, $message, $previousVehicle);

        // Save the fleet record
        $fleet->save();

        // Commit the transaction
        DB::commit();

        // Prepare success message
        $successMessage = $message ?? 'Car added to fleet successfully!';

        Log::info('Fleet created successfully', [
            'fleet_id' => $fleet->id,
            'instructor_id' => $instructor->id,
            'previous_vehicle' => $previousVehicle,
            'tracker_device' => $trackerUniqueId
        ]);

        return redirect()->route('fleet.index')
            ->with('message', $successMessage)
            ->with('alert_type', $previousVehicle ? 'warning' : 'success');

    } catch (\Exception $e) {
        // Rollback the transaction
        DB::rollBack();

        // Clean up uploaded file if transaction failed
        if (isset($carImageName) && file_exists(public_path('media/fleet/' . $carImageName))) {
            unlink(public_path('media/fleet/' . $carImageName));
        }

        Log::error('Fleet creation failed', [
            'error' => $e->getMessage(),
            'data' => $validatedData,
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()
            ->withErrors(['error' => $e->getMessage()])
            ->withInput();
    }
}

/**
 * Handle instructor assignment for store operation with reassignment
 */
private function handleInstructorAssignmentForStore(Fleet $fleet, string $instructorId, &$message, ?array $previousVehicle = null): void
{
    // Check if instructor already has any fleets assigned
    $existingFleets = Fleet::where('instructor_id', $instructorId)->get();

    if ($existingFleets->isEmpty()) {
        // No existing assignments, assign directly
        $fleet->instructor_id = $instructorId;
        $message = "Instructor assigned to {$fleet->car_brand_model} ({$fleet->car_registration_number}) successfully.";
    } else {
        // Instructor has existing assignments - unassign them
        $vehicleNames = $existingFleets->pluck('car_brand_model')->implode(', ');
        $vehicleCount = $existingFleets->count();

        // Unassign instructor from all previous fleets
        Fleet::where('instructor_id', $instructorId)
            ->update(['instructor_id' => null]);

        // Assign instructor to new fleet
        $fleet->instructor_id = $instructorId;

        // Create detailed message about reassignment
        if ($vehicleCount == 1) {
            $previousCar = $existingFleets->first();
            $message = "Instructor has been reassigned from {$previousCar->car_brand_model} ({$previousCar->car_registration_number}) to {$fleet->car_brand_model} ({$fleet->car_registration_number}).";
        } else {
            $message = "Instructor has been reassigned from {$vehicleCount} vehicles ({$vehicleNames}) to {$fleet->car_brand_model} ({$fleet->car_registration_number}).";
        }

        Log::info('Instructor reassigned during fleet creation', [
            'instructor_id' => $instructorId,
            'new_fleet_id' => $fleet->id,
            'new_vehicle' => $fleet->car_brand_model,
            'previous_vehicles' => $existingFleets->pluck('id')->toArray(),
            'previous_vehicle_names' => $vehicleNames
        ]);
    }
}

    /**
     * Create tracking device in external service
     */
    private function createTrackingDevice(array $data, Instructor $instructor): array
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
                    'instructor_id' => $instructor->id,
                    'instructor_name' => $instructor->fname . ' ' . $instructor->sname,
                    'created_by' => Auth::user()->id,
                    'created_by_name' => Auth::user()->name,
                    'created_at' => now()->toDateTimeString()
                ]
            ];

            // Call createDevice with the prepared data
            $result = $mbiraSyncService->createDevice($deviceData);

            // Validate the response
            if (!$result || !isset($result['id'])) {
                Log::warning('Invalid tracking device creation response', [
                    'device_id' => $data['tracker_device_id'],
                    'response' => $result
                ]);

                return [
                    'success' => false,
                    'error' => 'Invalid response from tracking device service'
                ];
            }

            // Log success
            Log::info('Tracking device created successfully', [
                'device_id' => $data['tracker_device_id'],
                'traccar_id' => $result['id'],
                'fleet_details' => [
                    'car' => $data['car_brand_model'],
                    'registration' => $data['reg_number']
                ]
            ]);

            return [
                'success' => true,
                'traccar_id' => $result['id'],
                'result' => $result
            ];

        } catch (\Exception $e) {
            Log::error('Tracking device creation failed', [
                'error' => $e->getMessage(),
                'device_id' => $data['tracker_device_id'],
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create tracking device: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Handle fleet image upload
     */
    private function handleImageUpload(Request $request): string
    {
        if (!$request->hasFile('fleet_image') || !$request->file('fleet_image')->isValid()) {
            return 'driving-school-car-default.png';
        }

        try {
            $file = $request->file('fleet_image');

            // Validate file type and size again for security
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                throw new \Exception('Invalid image type. Allowed: jpeg, png, jpg, gif');
            }

            if ($file->getSize() > $maxSize) {
                throw new \Exception('Image size exceeds 2MB limit');
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Move file to destination
            $destinationPath = public_path('media/fleet');

            // Create directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);

            Log::info('Fleet image uploaded successfully', [
                'filename' => $filename,
                'size' => $file->getSize()
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Fleet image upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $request->file('fleet_image')->getClientOriginalName()
            ]);

            // Return default image and let the process continue
            return 'driving-school-car-default.png';
        }
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
            'instructor' => 'required|exists:instructors,id',
            'licenceClass' => 'required|exists:licence_classes,id',
            'tracker_device_id' => 'nullable|string|unique:fleets,tracker_device_id,' . $fleet->id . '|max:50',
            'fleet_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'car_description' => 'nullable|string|max:1000',
        ], $messages);

        $trackingResult = null;
        $trackerDeviceId = null;
        $message = null;
        $previousVehicle = null;

        // Use database transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Handle the car image upload if present
            if ($request->hasFile('fleet_image')) {
                $carImageName = time() . '-' . $request->file('fleet_image')->getClientOriginalName();
                $request->file('fleet_image')->move(public_path('media/fleet'), $carImageName);
                $fleet->fleet_image = $carImageName;
            }

            // Check if instructor is being changed
            $instructorChanged = $fleet->instructor_id != $validatedData['instructor'];

            if ($instructorChanged) {
                // Get the previous instructor info for messaging
                $previousInstructor = $fleet->instructor_id ?
                    Instructor::find($fleet->instructor_id) : null;

                // Check if the new instructor is already assigned elsewhere
                $existingFleet = Fleet::where('instructor_id', $validatedData['instructor'])
                    ->where('id', '!=', $fleet->id)
                    ->first();

                if ($existingFleet) {
                    $previousVehicle = [
                        'brand_model' => $existingFleet->car_brand_model,
                        'registration' => $existingFleet->car_registration_number,
                        'instructor_name' => $previousInstructor ?
                            $previousInstructor->fname . ' ' . $previousInstructor->sname : 'None'
                    ];
                }
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
            }

            // Handle instructor assignment logic
            $this->handleInstructorAssignmentForUpdate($fleet, $validatedData['instructor'], $message, $previousVehicle);

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

            // Prepare success message
            $successMessage = $message ?? 'Fleet updated successfully!';

            // Redirect back with a success message
            return redirect()->route('fleet.index')
                ->with('message', $successMessage)
                ->with('alert_type', $previousVehicle ? 'warning' : 'success');

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
     * Handle instructor assignment for update operation with reassignment
     */
    private function handleInstructorAssignmentForUpdate(Fleet $fleet, string $instructorId, &$message, ?array $previousVehicle = null): void
    {
        // Check if the instructor is already assigned to this fleet
        if ($fleet->instructor_id === $instructorId) {
            return;
        }

        // Get the old instructor info for messaging
        $oldInstructorName = null;
        if ($fleet->instructor_id) {
            $oldInstructor = Instructor::find($fleet->instructor_id);
            $oldInstructorName = $oldInstructor ? $oldInstructor->fname . ' ' . $oldInstructor->sname : 'Unknown';
        }

        // Check if the new instructor already has any fleets assigned (excluding current)
        $existingFleets = Fleet::where('instructor_id', $instructorId)
            ->where('id', '!=', $fleet->id)
            ->get();

        if ($existingFleets->isEmpty()) {
            // No conflict, assign directly
            $fleet->instructor_id = $instructorId;

            if ($oldInstructorName) {
                $message = "Instructor changed from {$oldInstructorName} to new instructor assigned to {$fleet->car_brand_model}.";
            } else {
                $message = "Instructor assigned to {$fleet->car_brand_model} successfully.";
            }
        } else {
            // Instructor has existing assignments - unassign them
            $vehicleNames = $existingFleets->pluck('car_brand_model')->implode(', ');
            $vehicleCount = $existingFleets->count();

            // Unassign instructor from all previous fleets
            Fleet::where('instructor_id', $instructorId)
                ->where('id', '!=', $fleet->id)
                ->update(['instructor_id' => null]);

            // Assign instructor to current fleet
            $oldInstructorId = $fleet->instructor_id;
            $fleet->instructor_id = $instructorId;

            // Create detailed message about reassignment
            if ($vehicleCount == 1) {
                $previousCar = $existingFleets->first();
                $message = "Instructor has been reassigned from {$previousCar->car_brand_model} ({$previousCar->car_registration_number}) to {$fleet->car_brand_model} ({$fleet->car_registration_number}).";
            } else {
                $message = "Instructor has been reassigned from {$vehicleCount} vehicles ({$vehicleNames}) to {$fleet->car_brand_model} ({$fleet->car_registration_number}).";
            }

            // If there was a previous instructor, add that info
            if ($oldInstructorName) {
                $message .= " Previous instructor ({$oldInstructorName}) was unassigned from this vehicle.";
            }

            Log::info('Instructor reassigned during fleet update', [
                'instructor_id' => $instructorId,
                'fleet_id' => $fleet->id,
                'old_instructor_id' => $oldInstructorId,
                'new_vehicle' => $fleet->car_brand_model,
                'previous_vehicles' => $existingFleets->pluck('id')->toArray(),
                'previous_vehicle_names' => $vehicleNames
            ]);
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
