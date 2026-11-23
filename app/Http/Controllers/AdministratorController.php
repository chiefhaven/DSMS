<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Designation;
use App\Models\SalaryGrade;
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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\havenUtils;
use Yajra\DataTables\Facades\DataTables;

class AdministratorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view administrators')->only(['index', 'show', 'showProfile']);
        $this->middleware('permission:create administrators')->only(['create', 'store']);
        $this->middleware('permission:assign administrator roles')->only(['create', 'store']);
        $this->middleware('permission:edit administrators')->only(['edit', 'update']);
        $this->middleware('permission:delete administrators')->only('destroy');
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
                    $actions = '<li><a class="dropdown-item" href="' . route('administrators.show', $administrator) . '"><i class="fas fa-eye me-2"></i>View Profile</a></li>';
                    if (auth()->user()->can('edit administrators')) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('administrators.edit', $administrator) . '"><i class="fas fa-edit me-2"></i>Edit Profile</a></li>';
                    }
                    if (auth()->user()->can('delete administrators')) {
                        $actions .= '<li><hr class="dropdown-divider"></li>'
                            . '<li><form method="POST" action="' . route('administrators.destroy', $administrator) . '">'
                            . csrf_field() . method_field('DELETE')
                            . '<button class="dropdown-item text-danger delete-confirm" type="submit" data-name="'
                            . e(trim($administrator->fname . ' ' . $administrator->sname))
                            . '"><i class="fas fa-trash-alt me-2"></i>Delete</button>'
                            . '</form></li>';
                    }
                    return '<div class="dropdown">'
                        . '<button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>'
                        . '<ul class="dropdown-menu dropdown-menu-end">'
                        . $actions . '</ul></div>';
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
        $roleNames = ['admin', 'financeAdmin'];
        if (Auth::user()->can('manage system')) {
            $roleNames[] = 'superAdmin';
        }
        $role = Role::where('guard_name', 'web')->whereIn('name', $roleNames)->get();
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
            $administrator->employment_date = $post['employment_date'] ?? null;
            $administrator->employment_type = $post['employment_type'] ?? null;
            $administrator->end_date = $post['end_date'] ?? null;
            $administrator->paye = (bool) ($post['paye'] ?? false);
            $administrator->pension_applicable = (bool) ($post['pension_applicable'] ?? false);
            $administrator->terms_of_employment = $post['terms_of_employment'] ?? null;
            $administrator->contract_signed = (bool) ($post['contract_signed'] ?? false);
            $administrator->contract_signed_at = $post['contract_signed_at'] ?? null;
            $administrator->status = $post['status'] ?? 'Active';
            $administrator->bank_name = $post['bank_name'] ?? null;
            $administrator->bank_branch = $post['bank_branch'] ?? null;
            $administrator->bank_account_name = $post['bank_account_name'] ?? null;
            $administrator->bank_account_number = $post['bank_account_number'] ?? null;
            $administrator->bank_account_type = $post['bank_account_type'] ?? null;
            $administrator->district_id = $district;
            $administrator->save();

            $user = new User();
            $user->name = $post['username'];
            $user->administrator_id = $administrator->id;
            $user->email = $post['email'];
            $user->password = Hash::make($post['password']);
            $user->save();

            $user->assignRole($post['role']);

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
        $user = request()->user();
        $relations = ['User.roles', 'hrDepartment', 'designation', 'salaryGrade'];
        $canManagePayroll = $user?->can('process payroll') || $user?->can('view payroll');
        $isOwnProfile = $user && $user->administrator_id
            && (string) $user->administrator_id === (string) $administrator->id;
        if ($canManagePayroll || $isOwnProfile) {
            $relations['payrolls'] = function ($query) use ($canManagePayroll, $isOwnProfile) {
                $query->orderByDesc('payment_month');
                if ($isOwnProfile && !$canManagePayroll) {
                    $query->whereIn('status', ['Final', 'Paid']);
                }
            };
        }
        $administrator->load($relations);

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
        $administrator = Administrator::with(['User', 'salaryGrade'])->findOrFail($id);
        $district = District::get();
        $roleNames = ['admin', 'financeAdmin'];
        if (Auth::user()->can('manage system')) {
            $roleNames[] = 'superAdmin';
        }
        $role = Role::where('guard_name', 'web')->whereIn('name', $roleNames)->get();
        $genders = ['female' => 'Female', 'male' => 'Male', 'other' => 'Other'];
        $hrDepartments = Auth::user()->can('process payroll')
            ? \App\Models\HrDepartment::orderBy('name')->get()
            : collect();
        $designations = Auth::user()->can('process payroll')
            ? \App\Models\Designation::orderBy('name')->get()
            : collect();
        $salaryGrades = Auth::user()->can('process payroll')
            ? SalaryGrade::orderBy('code')->get()
            : collect();
        return view('administrators.editadministrator', compact('administrator', 'district', 'role', 'genders', 'hrDepartments', 'designations', 'salaryGrades'));
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

        $directory = config('bank_directory', []);
        $bankName = $request->input('bank_name');
        $branchNames = is_string($bankName) && isset($directory[$bankName])
            ? array_keys($directory[$bankName]['branches'] ?? [])
            : [];

        $this->validate($request, [
            'first_name' => 'required',
            'sir_name' => 'required',
            'email' => ($user ? 'required' : 'nullable') . '|email|unique:users,email,' . optional($user)->id,
            'address' => 'required',
            'gender' => 'required',
            'date_of_birth' => 'required',
            'employment_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', Rule::in(['Full time', 'Contract', 'Part-time'])],
            'end_date' => ['nullable', 'date'],
            'terms_of_employment' => ['nullable', 'string'],
            'contract_signed' => ['nullable', 'boolean'],
            'contract_signed_at' => ['nullable', 'date'],
            'paye' => ['nullable', 'boolean'],
            'pension_applicable' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['Active', 'Pending', 'Suspended', 'Contract ended'])],
            'district' => 'required',
            'role' => 'nullable|in:admin,financeAdmin,superAdmin',
            'phone' => 'required',
            'bank_name' => ['nullable', 'string', Rule::in(array_keys($directory)), 'required_with:bank_branch,bank_account_name,bank_account_number,bank_account_type'],
            'bank_branch' => ['nullable', 'string', Rule::in($branchNames), 'required_with:bank_name,bank_account_name,bank_account_number,bank_account_type'],
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_type' => 'nullable|string|max:100',
        ], $messages);

        $post = $request->all();
        $roleName = $request->input('role');
        $createAccount = !$user && ($request->filled('email') || $request->filled('role') || $request->filled('password'));
        if ($createAccount) {
            abort_unless($request->user()->can('manage administrator accounts'), 403);
            abort_unless($request->user()->can('assign administrator roles'), 403);
            $this->validate($request, ['email' => 'required|email', 'role' => 'required|in:admin,financeAdmin,superAdmin']);
        } elseif ($user && ($post['email'] !== $user->email || $request->filled('password'))) {
            abort_unless($request->user()->can('manage administrator accounts'), 403);
        }
        $changeRole = $roleName && (!$user || $user->getRoleNames()->all() !== [$roleName]);
        if ($changeRole) {
            abort_unless($request->user()->can('assign administrator roles'), 403);
            if ($roleName === 'superAdmin') {
                abort_unless($request->user()->can('manage system'), 403);
            }
        }

        $district = havenUtils::selectDistrict($post['district']);

        // Update Administrator
        $Administrator->fname = $post['first_name'];
        $Administrator->sname = $post['sir_name'];
        $Administrator->gender = $post['gender'];
        $Administrator->phone = $post['phone'];
        $Administrator->address = $post['address'];
        $Administrator->date_of_birth = $post['date_of_birth'];
        if ($request->exists('employment_date')) {
            $Administrator->employment_date = $post['employment_date'] ?? null;
        }
        if ($request->exists('employment_type')) {
            $Administrator->employment_type = $post['employment_type'] ?? null;
        }
        if ($request->exists('end_date')) {
            $Administrator->end_date = $post['end_date'] ?? null;
        }
        if ($request->exists('paye')) {
            $Administrator->paye = (bool) ($post['paye'] ?? false);
        }
        if ($request->exists('pension_applicable')) {
            $Administrator->pension_applicable = (bool) ($post['pension_applicable'] ?? false);
        }
        if ($request->exists('terms_of_employment')) {
            $Administrator->terms_of_employment = $post['terms_of_employment'] ?? null;
        }
        if ($request->exists('contract_signed')) {
            $Administrator->contract_signed = (bool) ($post['contract_signed'] ?? false);
        }
        if ($request->exists('contract_signed_at')) {
            $Administrator->contract_signed_at = $post['contract_signed_at'] ?? null;
        }
        if ($request->exists('status')) {
            $Administrator->status = $post['status'] ?? $Administrator->status;
        }
        if ($request->exists('bank_name') || $request->exists('bank_branch')) {
            $Administrator->bank_name = $post['bank_name'] ?? null;
            $Administrator->bank_branch = $post['bank_branch'] ?? null;
        }
        foreach (['bank_account_name', 'bank_account_number', 'bank_account_type'] as $field) {
            if ($request->exists($field)) {
                $Administrator->{$field} = $post[$field] ?? null;
            }
        }
        $Administrator->district_id = $district;

        if ($request->user()->can('process payroll')
            && ($request->exists('hr_department_id') || $request->exists('basic_salary') || $request->exists('designation_id') || $request->exists('salary_grade_id'))) {
            $hrm = $request->validate([
                'hr_department_id' => ['nullable', Rule::exists('hr_departments', 'id')->whereNull('deleted_at')],
                'designation_id' => ['nullable', Rule::exists('designations', 'id')->whereNull('deleted_at')],
                'salary_grade_id' => ['nullable', Rule::exists('salary_grades', 'id')->whereNull('deleted_at')],
                'basic_salary' => 'nullable|numeric|min:0',
            ]);
            if (!empty($hrm['designation_id'])) {
                $designation = Designation::findOrFail($hrm['designation_id']);
                $departmentId = $request->exists('hr_department_id')
                    ? ($hrm['hr_department_id'] ?? null)
                    : $Administrator->hr_department_id;
                $isUnchangedAssignment = $designation->id === $Administrator->designation_id
                    && $departmentId === $Administrator->hr_department_id;

                if ($designation->hr_department_id !== $departmentId && !$isUnchangedAssignment) {
                    throw ValidationException::withMessages([
                        'designation_id' => 'The selected designation does not belong to the selected HR department.',
                    ]);
                }
            }
            if ($request->exists('hr_department_id')) {
                $Administrator->hr_department_id = $hrm['hr_department_id'] ?? null;
            }
            if ($request->exists('designation_id')) {
                $Administrator->designation_id = $hrm['designation_id'] ?? null;
            }
            if ($request->exists('salary_grade_id')) {
                $Administrator->salary_grade_id = $hrm['salary_grade_id'] ?? null;
            }
            if ($request->exists('basic_salary')) {
                $Administrator->basic_salary = $hrm['basic_salary'] ?? null;
            }
        }

        $Administrator->save();

        // Update or Create User
        if ($createAccount) {
            $user = new User();
            $user->administrator_id = $post['administrator_id'];
            $user->name = $post['sir_name'];
            $user->password = Hash::make(Str::random(10));
        }

        if ($user) {
            $user->email = $post['email'];

            if (!empty($post['password'])) {
                $user->password = Hash::make($post['password']);
            }

            $user->save();

            if ($changeRole) {
                $user->syncRoles([$roleName]);
            }
        }

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
