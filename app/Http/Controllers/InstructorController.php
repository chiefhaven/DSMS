<?php
namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\District;
use App\Models\Lesson;
use App\Models\Permission;
use App\Models\Role;
use App\Http\Requests\StoreInstructorRequest;
use App\Http\Requests\UpdateInstructorRequest;
use App\Models\Administrator;
use App\Models\Department;
use App\Models\Fleet;
use App\Models\Student;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Session;
use Illuminate\Support\Str;
use PDF;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\Facades\DataTables;

class InstructorController extends Controller
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
        $instructors = Instructor::with('User', 'Lesson', 'Fleet')->get();
        return view('instructors.instructors', compact('instructors'));
    }

    public function fetchStudents(Request $request): JsonResponse
    {
        // Capture the search keyword from the request if provided
        $search = $request->input('search.value'); // This is the global search input

        $status = $request->status;

        $students = Student::with(['user', 'course', 'fleet', 'invoice', 'classroom', 'trainingLevel'])
            ->when($status === 'active', function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('fleet_id')
                    ->orWhereNotNull('classroom_id');
                })->where('status', '!=', 'Finished');
            })
            ->when($status === 'unassigned', function ($query) {
                // Unassigned means no fleet or classroom assigned and not finished
                $query->whereNull('fleet_id')
                    ->whereNull('classroom_id')
                    ->where('status', '!=', 'Finished');
            })
            ->when($status === 'finished', function ($query) {
                $query->where('status', 'Finished');
            })
            ->orderBy('created_at', 'desc');

        if (Auth::user()->hasRole('instructor')) {

            $instructor = Auth::user()->instructor;

            if ($instructor->department) {
                $departmentName = $instructor->department->name;

                switch ($departmentName) {
                    case 'practical':
                        $fleetAssigned = Fleet::where('instructor_id', $instructor->id)->first();
                        if ($fleetAssigned) {
                            $students->where('fleet_id', $fleetAssigned->id);
                        } else {
                            throw new ModelNotFoundException(__('You are not allocated a car.'));
                        }
                        break;

                    case 'theory':
                        $classroomIds = $instructor->classrooms->pluck('id');
                        if ($classroomIds->isNotEmpty()) {
                            $students->whereIn('classroom_id', $classroomIds);
                        } else {
                            throw new ModelNotFoundException(__('You are not allocated a class room.'));
                        }
                        break;
                }
            }
        }

        // Apply the search filter to the 'fname', 'mname', and 'sname' columns
        if ($search) {
            $students->where(function($query) use ($search) {
                $query->where('fname', 'like', "%$search%")
                    ->orWhere('mname', 'like', "%$search%")
                    ->orWhere('sname', 'like', "%$search%")
                    ->orWhereHas('course', function($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        return DataTables::of($students)
            ->addColumn('full_name', function ($student) {
                $middle = $student->mname ? $student->mname . ' ' : '';
                return '<span class="text-uppercase">' . e($student->fname) . ' ' . e($middle) . '<b>' . e($student->sname) . '</b></span>';
            })
            ->addColumn('course_enrolled', function ($student) {
                return $student->course->name ?? 'Not enrolled yet';
            })
            ->addColumn('fees', function ($student) {
                return 'K' . number_format($student->invoice->invoice_total ?? 0);
            })
            ->addColumn('balance', function ($student) {
                $invoiceBalance = $student->invoice->invoice_balance ?? 0;
                $balanceClass = $invoiceBalance > 0 ? 'text-danger' : 'text-success';

                return '<strong>
                            <span class="' . $balanceClass . '">
                                K' . number_format($invoiceBalance, 2) . '
                            </span>
                        </strong>';
            })
            ->addColumn('registered_on', function ($student) {
                return $student->created_at->format('d M, Y');
            })
            ->addColumn('car_assigned', function ($student) {
                if ($student->fleet) {
                    return $student->fleet->car_registration_number;
                } elseif ($student->classroom) {
                    return $student->classroom->name;
                } else {
                    return 'Not assigned';
                }
            })
            ->addColumn('attendance', function ($student) {
                $attendanceCount = $student->attendance ? $student->attendance->count() : 0;
                $courseDuration = $student->course->duration ?? 0;
                $percentage = ($courseDuration > 0) ? round(($attendanceCount / $courseDuration) * 100, 1) : 0;

                if ($percentage >= 100) {
                    return '<span class="badge bg-success">Completed</span>';
                } elseif ($percentage >= 50) {
                    return '<span class="badge bg-info">' . $percentage . '%</span>';
                } else {
                    return '<span class="badge bg-warning">' . $percentage . '%</span>';
                }
            })
            ->addColumn('course_status', function ($student) {
                return '<span class="status-span"
                            data-status="' . e($student->status) . '"
                            data-fname="' . e($student->fname) . '"
                            data-mname="' . e($student->mname) . '"
                            data-sname="' . e($student->sname) . '"
                            data-id="' . e($student->id) . '"
                            style="cursor: pointer; color: #0d6efd;">' . ucfirst($student->status) . '</span>';
            })
            ->addColumn('phone', function ($student) {
                return $student->phone ?? '-';
            })
            ->addColumn('email', function ($student) {
                return $student->user->email ?? '-';
            })
            ->addColumn('trn', function ($student) {
                return $student->trn ?? '-';
            })
            ->addColumn('actions', function ($student) {
                $view = '<a class="dropdown-item" href="' . url('/viewstudent', $student->id) . '">
                            <i class="fa fa-user"></i> View
                        </a>';

                $edit = '';
                $delete = '';
                $paymentReminder = '';

                if (auth()->user()->hasRole(['superAdmin', 'admin']) && ($student->invoice->invoice_balance ?? 0) > 0) {
                    $paymentReminder = '
                        <form method="POST" action="' . url('send-balance-sms', [$student->id, 'Balance']) . '">
                            ' . csrf_field() . '
                            <button class="dropdown-item" type="submit">
                                <i class="fas fa-bell me-2"></i> Send balance reminder
                            </button>
                        </form>
                    ';
                }

                $changeStatus = "<button
                                    class='dropdown-item change-status-btn'
                                    data-fname=\"" . e($student->fname) . "\"
                                    data-mname=\"" . e($student->mname) . "\"
                                    data-sname=\"" . e($student->sname) . "\"
                                    data-id=\"" . e($student->id) . "\"
                                    data-status=\"" . e($student->status) . "\">
                                        <i class='fas fa-toggle-on'></i> Change status
                                </button>";

                if (auth()->user()->hasRole('superAdmin')) {
                    $edit = '<a class="dropdown-item" href="' . url('/edit-student', $student->id) . '">
                                <i class="fa fa-pencil"></i> Edit
                            </a>';

                    $delete = '<form method="POST" action="' . url('student-delete', $student->id) . '" style="display:inline;">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="dropdown-item delete-confirm">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </form>';
                }

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $view . $edit . $paymentReminder . $delete . $changeStatus . '
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['actions', 'full_name', 'attendance', 'balance', 'course_status']) // allow HTML in 'actions'
            ->make(true);
    }

    public function indexInstructors(Request $request)
    {
        // Validate the query parameters
        $validated = $request->validate([
            'status' => 'nullable|string|in:active,inactive',
            'department' => 'nullable|string',
        ]);

        // Apply filters dynamically
        $query = Instructor::with(['User', 'Lesson', 'Fleet']);

        if ($request->has('status')) {
            $query->where('status', $validated['status']);
        }

        $instructors = $query->whereHas('department', function($query) {
            $query->where('name', 'Theory');
        })->get(); // Adjust the number per page as needed

        return response()->json($instructors);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $district = District::get();
        $instructor = null;
        $departments = Department::get();
        return view('instructors.addinstructor', compact('district', 'departments', 'instructor'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreInstructorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstructorRequest $request)
    {
        $messages = [
            'first_name.required'      => 'The "First name" field is required!',
            'sir_name.required'        => 'The "Surname" field is required!',
            'email.required'           => 'The "Email" field is required!',
            'email.unique'             => 'The "Email" is already in use!',
            'gender.required'          => 'The "Gender" field is required!',
            'date_of_birth.required'   => 'The "Date of Birth" field is required!',
            'date_of_birth.before'     => 'Instuctors age must be at least 18 years old!',
            'department.required'      => 'The "Department" field is required!',
            'department.exists'        => 'The selected "Department" is invalid!',
            'address.required'         => 'The "Address" field is required!',
            'district.required'        => 'The "District" field is required!',
            'phone.required'           => 'The "Phone" field is required!',
        ];

        $this->validate($request, [
            'first_name'    => 'required',
            'sir_name'      => 'required',
            'email'         => 'required|unique:users',
            'address'       => 'required',
            'gender'        => 'required',
            'date_of_birth' => ['required', 'date', 'before:' . now()->subYears(18)->format('Y-m-d')],
            'district'      => 'required',
            'phone'         => 'required',
            'department'    => 'required|exists:departments,id',
        ], $messages);

        $post = $request->All();

        $district = havenUtils::selectDistrict($post['district']);

        $instructor = new instructor;

        $instructor->fname = $post['first_name'];
        $instructor->sname = $post['sir_name'];
        $instructor->gender = $post['gender'];
        $instructor->phone = $post['phone'];
        $instructor->address = $post['address'];
        $instructor->status = $post['status'];
        $instructor->date_of_birth = $post['date_of_birth'];
        $instructor->district_id = $district;
        $instructor->department_id = $post['department'];

        $instructor->save();


        $user = new User;
        $user->name = $post['username'];
        $user->instructor_id = $instructor->id;
        $user->email = $post['email'];
        $user->password = Hash::make($post['password']);
        $user->save();

        $user->assignRole('instructor');

        return redirect()->back()->with('message', 'Instructor added!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function show($instructor)
    {
        $instructor = Instructor::with([
            'user',
            'lesson',
            'fleet.student.invoice',
            'classrooms.students.invoice'
        ])->findOrFail($instructor);

        return view('instructors.viewinstructor', compact('instructor'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function instructorData($instructor)
    {
        $instructor = Instructor::with('Fleet.student.invoice', 'classrooms.students.invoice', 'attendances.student', 'attendances.lesson', 'Schedules')->find($instructor);
        return response()->json($instructor);
    }

    /**
     * Display own profile.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function showProfile()
    {
        $instructor = Instructor::find(Auth::user()->instructor_id);
        return view('instructors.viewinstructor', compact('instructor'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $instructor = Instructor::with('User')->find($id);
        $district = district::get();
        $departments = Department::get();
        return view('instructors.editinstructor', [ 'instructor' => $instructor ], compact('instructor', 'district', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInstructorRequest  $request
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInstructorRequest $request, Instructor $instructor)
    {
        $messages = [
            'first_name.required'      => 'The "First name" field is required!',
            'sir_name.required'        => 'The "Surname" field is required!',
            'email.required'           => 'The "Email" field is required!',
            'email.unique'             => 'The "Email" is already in use!',
            'gender.required'          => 'The "Gender" field is required!',
            'date_of_birth.required'   => 'The "Date of Birth" field is required!',
            'date_of_birth.before'     => 'Instructors age must be at least 18 years old!',
            'department.required'      => 'The "Department" field is required!',
            'department.exists'        => 'The selected "Department" is invalid!',
            'address.required'         => 'The "Address" field is required!',
            'district.required'        => 'The "District" field is required!',
            'phone.required'           => 'The "Phone" field is required!',
        ];

        $this->validate($request, [
            'first_name'    => 'required',
            'sir_name'      => 'required',
            'email'          => 'required|unique:users,email,' . Instructor::find($request->instructor_id)->user->id,
            'address'       => 'required',
            'gender'        => 'required',
            'date_of_birth' => ['required', 'date', 'before:' . now()->subYears(18)->format('Y-m-d')],
            'district'      => 'required',
            'phone'         => 'required',
            'department'    => 'required|exists:departments,id',
            'status'        => 'required|in:active,pending,suspended,contract ended',
        ], $messages);

        $post = $request->all();

        $district = havenUtils::selectDistrict($post['district']);

        $instructor = Instructor::find($request->instructor_id);

        // Update instructor data
        $instructor->fname = $post['first_name'];
        $instructor->sname = $post['sir_name'];
        $instructor->gender = $post['gender'];
        $instructor->phone = $post['phone'];
        $instructor->address = $post['address'];
        $instructor->status = $post['status'];
        $instructor->date_of_birth = $post['date_of_birth'];
        $instructor->district_id = $district;
        if ($instructor->department_id != $post['department']) {
            if($instructor->fleet){
                $instructor->fleet->instructor_id = null;
                $instructor->fleet->save();
            }
        }
        $instructor->department_id = $post['department'];
        $instructor->save();

        // Update associated user
        $user = $instructor->User;

        $user->email = $post['email'];
        if (!empty($post['password'])) {
            $user->password = Hash::make($post['password']);
        }

        $user->save();

        return redirect('/instructors')->with('message', 'Instructor updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $instructor = Instructor::find($id);

        // Check if the instructor exists
        if ($instructor) {
            // Store the instructor's name for alert
            $instructorName = $instructor->fname . " " . $instructor->sname;

            // Start a database transaction to ensure consistency
            DB::beginTransaction();

            try {
                // Delete related users with the instructor_id
                User::where('instructor_id', $id)->delete();

                // Delete the instructor record
                $instructor->delete();

                // Commit the transaction
                DB::commit();

                // Show success message
                Alert::toast('Instructor ' . $instructorName . ' deleted', 'success');
            } catch (\Exception $e) {
                // Rollback in case of error
                DB::rollBack();

                // Log the error for debugging
                Log::error('Error deleting instructor: ' . $e->getMessage());

                // Show error message
                Alert::toast('Failed to delete instructor. Please try again later.', 'error');
            }
        } else {
            // Show error message if instructor not found
            Alert::toast('Instructor not found', 'error');
        }

        // Redirect to the instructors page
        return redirect('/instructors');
    }

    public function instructorSearch(Request $request)
    {
        $datas = Instructor::select("fname", "sname")
            ->where("fname","LIKE","%{$request->instructor}%")
            ->orWhere("sname","LIKE","%{$request->instructor}%")
            ->get();

        $dataModified = array();

        foreach ($datas as $instructor){
           $dataModified[] = $instructor->fname.' '.$instructor->sname;
         }

        return response()->json($dataModified);
    }
}
