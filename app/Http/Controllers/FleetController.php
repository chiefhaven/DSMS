<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\Instructor;
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
        $this->middleware(['role:superAdmin'], ['role:admin']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $fleet = Fleet::with('Instructor')->get();
        $instructor = Instructor::get();
        return view('fleet.fleet', compact('fleet', 'instructor'));
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
    public function show(Fleet $fleet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function edit(Fleet $fleet)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $fleet = Fleet::find($id)->delete();

        $message ="Fleet deleted";

        Alert::toast('Fleet deleted', 'success');

        return redirect()->back()->with('message', $message);
    }
}
