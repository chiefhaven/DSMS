<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\District;
use App\Models\Lesson;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\StoreAdministratorRequest;
use App\Http\Requests\UpdateAdministratorRequest;
use Session;
use Auth;
use Illuminate\Support\Str;
use PDF;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class AdministratorController extends Controller
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
        $administrator = Administrator::with('User')->get();
        return view('administrators.administrators', compact('administrator'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $district = District::get();
        $role = Role::get();
        return view('administrators.addadministrator', compact('district', 'role'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAdministratorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAdministratorRequest $request)
    {
        $messages = [
            'fname.required' => 'First name is required',
            'sname.required'   => 'Sirname is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email is already in use',
            'gender.required'   => 'The "Gender" field required',
            'date_of_birth.required' => 'Date of birth is required',
            'role.required' => 'Role is required',
            'password.required' => 'Password is required',
        ];

        // Validate the request
        $this->validate($request, [
            'first_name'  =>'required',
            'sir_name' =>'required',
            'email'   =>'required | unique:users,email',
            'address' =>'required',
            'gender'  =>'required',
            'date_of_birth' =>'required',
            'district' =>'required',
            'role' =>'required',
            'phone' =>'required',
            'password' =>'required'

        ], $messages);

        $post = $request->All();

        $district = havenUtils::selectDistrict($post['district']);

        $administrator = new Administrator;

        $administrator->fname = $post['first_name'];
        $administrator->sname = $post['sir_name'];
        $administrator->gender = $post['gender'];
        $administrator->phone = $post['phone'];
        $administrator->address = $post['address'];
        $administrator->date_of_birth = $post['date_of_birth'];
        $administrator->district_id = $district;

        $administrator->save();

        $user = new User;
        $user->name = $post['username'];
        $user->administrator_id = $administrator->id;
        $user->email = $post['email'];
        $user->password = Hash::make($post['password']);

        $user->save();
        if($post['role'] == 'superAdmin'){

            $user->assignRole('superAdmin');
        }

        else{

            $user->assignRole('admin');
        }


        Alert::toast('Administrator'.' '.$administrator->fname.' '.'added successifully', 'success');
        return redirect('/administrators');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function show(Administrator $Administrator)
    {
        $userAdmin = User::with('Administrator')->find(Auth::user()->id);
        return view('administrators.viewadministrator', [ 'userAdmin' => $userAdmin ], compact('userAdmin'));
    }

    /**
     * Display own profile.
     *
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function showProfile()
    {
        $Administrator = Administrator::find(Auth::user()->Administrator_id);
        return view('administrators.viewadministrator', compact('administrator'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $administrator = Administrator::with('User')->find($id);
        $district = District::get();
        $role = Role::get();
        $genders = ['female' => 'Female', 'male' => 'Male', 'other' => 'Other'];
        return view('administrators.editadministrator', [ 'administrator' => $administrator ], compact('administrator', 'district', 'role', 'genders'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAdministratorRequest  $request
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAdministratorRequest $request, Administrator $Administrator)
    {
        $messages = [
            'first_name.required' => 'Firstname is required',
            'sir_name.required' => 'Surname is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email address is already in use',
            'gender.required' => 'The Gender field is required',
            'date_of_birth.required' => 'Date of birth is required',
            'role.required' => 'Role is required',
        ];

        $user = User::where('administrator_id', $request->administrator_id)->first();

        $this->validate($request, [
            'first_name' => 'required',
            'sir_name' => 'required',
            'email' => 'required|email|unique:users,email,' . optional($user)->id,
            'address' => 'required',
            'gender' => 'required',
            'date_of_birth' => 'required',
            'district' => 'required',
            'role' => 'required',
            'phone' => 'required'
        ], $messages);

        $post = $request->all();

        $district = havenUtils::selectDistrict($post['district']);

        // Update Administrator
        $Administrator->fname = $post['first_name'];
        $Administrator->sname = $post['sir_name'];
        $Administrator->gender = $post['gender'];
        $Administrator->phone = $post['phone'];
        $Administrator->address = $post['address'];
        $Administrator->date_of_birth = $post['date_of_birth'];
        $Administrator->district_id = $district;
        $Administrator->save();

        // Update or Create User
        if (!$user) {
            $user = new User();
            $user->administrator_id = $post['administrator_id'];
            $user->name = $post['sir_name'];
            $user->password = Hash::make(Str::random(10));
        }

        $user->email = $post['email'];

        if (!empty($post['password'])) {
            $user->password = Hash::make($post['password']);
        }

        $user->save();

        // Sync Roles
        $user->syncRoles([
            match ($post['role']) {
                'superAdmin' => 'superAdmin',
                'financeAdmin' => 'financeAdmin',
                default => 'admin'
            }
        ]);

        return redirect('/administrators')->with('message', 'Administrator updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

            $administrator = Administrator::find($id);

            $administrator->delete();

            $administratorName = $administrator->fname." ". $administrator->sname;

            User::where('administrator_id', $id)->delete();

            $message ="Administrator deleted";

            return redirect('/administrators')->with('message', $message);

    }
}
