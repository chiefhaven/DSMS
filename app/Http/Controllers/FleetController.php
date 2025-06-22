<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\Instructor;
use App\Models\Student;
use App\Http\Requests\StoreFleetRequest;
use App\Http\Requests\UpdateFleetRequest;
use Session;
use Illuminate\Support\Str;
use PDF;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Permission;
use App\Models\Role;

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
        $fleet = Fleet::with('Instructor')->get();

        $instructors = Instructor::whereHas('department', function($query) {
            $query->where('name', 'Practical');
        })->get();

        $student = Student::get();
        return view('fleet.fleet', compact('fleet', 'instructors', 'student'));
    }

    public function getFleet()
    {
        $fleet = Fleet::with('Instructor')->get();
        return response()->json($fleet, 200);
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
            'reg_number.required'   => 'The "car number plate" field is should be unique!',
        ];

        // Validate the request
        $this->validate($request, [
            'car_brand_model'  =>'required',
            'reg_number' =>'required'

        ], $messages);

        $post = $request->All();

        $fleet = new fleet;

        //car image processing
        if($request->file('fleet_image')){
            $carImageName = time().'-'.$request->file('fleet_image')->getClientOriginalName();
            $request->fleet_image->move(public_path('media/fleet'), $carImageName);
            $fleet->fleet_image = $carImageName;
        }

        else{

            $fleet->fleet_image = 'driving-school-car-default.png';
        }

        if(isset($post['instructor'])){

            $instructorID = havenUtils::instructorID($post['instructor']);

            if(isset($instructorID)){

                $fleet->instructor_id = $instructorID;

            }
            else{
                $fleet->instructor_id = 1000000;
            }
        }

        else{
                $fleet->instructor_id = 1000000;
        }


        $fleet->car_brand_model = $post['car_brand_model'];
        $fleet->car_registration_number = $post['reg_number'];
        $fleet->car_description = $post['car_description'];

        $fleet->save();

        return redirect()->back()->with('message', 'car added to fleet!');
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

        return view('fleet.editfleet', [ 'fleet' => $fleet ], compact('fleet', 'instructors'));
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
        ];

        // Validate the request data
        $request->validate([
            'car_brand_model' => 'required',
            'reg_number' => 'required',
            'instructor' => 'required',
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

        // Check if the instructor is already assigned to another fleet
        if ($fleetCount < 1 ) {
            $fleet->instructor_id = $post['instructor'] ?? 1000000;  // Assign instructor, default to 1000000 if not provided
        }
        elseif ($fleetCount > 0) {
            // Unassign the instructor from all other fleets
            $fleets = Fleet::where('instructor_id', $post['instructor'])->get();
            foreach ($fleets as $fleet_1) {
                $fleet_1->instructor_id = null;
                $fleet_1->save(); // Save the unassigned fleet
            }

            // Assign the instructor to the current fleet
            $fleet->instructor_id = $post['instructor'];

            $message = 'Instructor was assigned to different car, has been unassigned and reassigned to '.$fleet->car_brand_model;
        }
        else {
            // If no instructor is provided, assign a default value
            $fleet->instructor_id = null;
            $message = 'Instructor has been unassigned';
        }

        // Update the fleet details
        $fleet->car_brand_model = $post['car_brand_model'];
        $fleet->car_registration_number = $post['reg_number'];
        $fleet->car_description = $post['car_description'];

        // Save the updated fleet
        $fleet->save();

        // Redirect back with a success message
        return redirect()->route('fleet')->with('message', $message ?? 'Fleet updated successfully!');
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
