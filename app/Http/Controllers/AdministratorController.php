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
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\havenUtils;
use Yajra\DataTables\Facades\DataTables;

class AdministratorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage administration']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $search = trim((string) $request->input('search.value', ''));

            return DataTables::of(Administrator::query()->with('User.roles')->select('administrators.*'))
                ->filter(function ($query) use ($search) {
                    if ($search !== '') {
                        $query->where(function ($query) use ($search) {
                            $query->where('fname', 'like', "%{$search}%")
                                ->orWhere('sname', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhereHas('User', fn ($user) => $user->where('email', 'like', "%{$search}%"))
                                ->orWhereHas('User.roles', fn ($roles) => $roles->where('name', 'like', "%{$search}%"));
                        });
                    }
                })
                ->addColumn('administrator', fn (Administrator $administrator) =>
                    trim($administrator->fname . ' ' . $administrator->sname)
                )
                ->addColumn('email', fn (Administrator $administrator) => $administrator->User?->email ?? 'N/A')
                ->addColumn('roles', function (Administrator $administrator) {
                    $roles = $administrator->User?->roles;
                    if (!$roles || $roles->isEmpty()) {
                        return '<span class="badge bg-secondary">Staff</span>';
                    }

                    return $roles->map(function ($role) {
                        $name = match ($role->name) {
                            'admin' => 'Admin',
                            'superAdmin' => 'Super Admin',
                            'financeAdmin' => 'Finance Admin',
                            default => ucfirst($role->name),
                        };

                        return '<span class="badge bg-primary me-1">' . e($name) . '</span>';
                    })->implode(' ');
                })
                ->addColumn('actions', function (Administrator $administrator) {
                    return '<div class="dropdown">'
                        . '<button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>'
                        . '<ul class="dropdown-menu dropdown-menu-end">'
                        . '<li><a class="dropdown-item" href="' . route('administrators.show', $administrator) . '"><i class="fas fa-eye me-2"></i>View Profile</a></li>'
                        . '<li><a class="dropdown-item" href="' . route('administrators.edit', $administrator) . '"><i class="fas fa-edit me-2"></i>Edit Profile</a></li>'
                        . '<li><hr class="dropdown-divider"></li>'
                        . '<li><form method="POST" action="' . route('administrators.destroy', $administrator) . '">'
                        . csrf_field() . method_field('DELETE')
                        . '<button class="dropdown-item text-danger delete-confirm" type="submit" data-name="'
                        . e(trim($administrator->fname . ' ' . $administrator->sname))
                        . '"><i class="fas fa-trash-alt me-2"></i>Delete</button>'
                        . '</form></li></ul></div>';
                })
                ->only(['actions', 'administrator', 'roles', 'email', 'phone'])
                ->rawColumns(['roles', 'actions'])
                ->toJson();
        }

        return view('administrators.administrators');
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
            'first_name.required' => 'First name is required',
            'sir_name.required' => 'Surname is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email is already in use',
            'gender.required' => 'The Gender field is required',
            'date_of_birth.required' => 'Date of birth is required',
            'role.required' => 'Role is required',
            'password.required' => 'Password is required',
        ];

        $request->validated();

        $post = $request->validated();

        DB::transaction(function () use ($post) {

            $district = havenUtils::selectDistrict($post['district'] ?? null);

            $administrator = new Administrator();
            $administrator->fname = $post['first_name'];
            $administrator->sname = $post['sir_name'];
            $administrator->gender = $post['gender'];
            $administrator->phone = $post['phone'];
            $administrator->address = $post['address'];
            $administrator->date_of_birth = $post['date_of_birth'];
            $administrator->district_id = $district;
            $administrator->save();

            $user = new User();
            $user->name = $post['username'];
            $user->administrator_id = $administrator->id;
            $user->email = $post['email'];
            $user->password = Hash::make($post['password']);
            $user->save();

            $user->assignRole(
                match ($post['role']) {
                    'superAdmin' => 'superAdmin',
                    'financeAdmin' => 'financeAdmin',
                    default => 'admin'
                }
            );

            Alert::toast(
                'Administrator ' . $administrator->fname . ' added successfully',
                'success'
            );
        });

        return redirect()->route('administrators.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function show(Administrator $administrator)
    {
        if (!$administrator->exists) {
            $administrator = Administrator::findOrFail(Auth::user()->administrator_id);
        }
        $administrator->load('User.roles');

        return view('administrators.viewadministrator', compact('administrator'));
    }

    /**
     * Display own profile.
     *
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function showProfile()
    {
        return $this->show(Administrator::findOrFail(Auth::user()->administrator_id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $administrator = Administrator::with('User')->findOrFail($id);
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
    public function update(UpdateAdministratorRequest $request, Administrator $administrator)
    {
        if (!$administrator->exists) {
            $administrator = Administrator::findOrFail($request->input('administrator_id'));
        }
        $request->merge(['administrator_id' => $administrator->id]);
        $Administrator = $administrator;

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

        return redirect()->route('administrators.index')->with('message', 'Administrator updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Administrator  $Administrator
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

            $administrator = Administrator::findOrFail($id);

            $administrator->delete();

            $administratorName = $administrator->fname." ". $administrator->sname;

            User::where('administrator_id', $id)->delete();

            $message ="Administrator deleted";

            return redirect()->route('administrators.index')->with('message', $message);

    }
}
