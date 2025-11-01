<?php

namespace App\Http\Controllers;
use App\Http\Controllers\havenUtils;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\User;
use App\Models\District;
use App\Models\Attendance;
use App\Models\Setting;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\PdfStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Administrator;
use App\Models\Classroom;
use App\Models\Fleet;
use App\Models\Instructor;
use App\Models\TrainingLevel;
use App\Notifications\StudentCarAssigned;
use App\Notifications\StudentClassAssignment;
use App\Notifications\StudentRegistered;
use Illuminate\Support\Str;
use PDF;
use RealRashid\SweetAlert\Facades\Alert;
use Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:superAdmin|admin|instructor|financeAdmin']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
                $query->whereNull('fleet_id')
                    ->whereNull('classroom_id')
                    ->where('status', '!=', 'Finished');
            })
            ->when($status === 'finished', function ($query) {
                $query->where('status', 'Finished');
            })
            ->when($status === 'theory', function ($query) {
                $traingingLevel = TrainingLevel::where('name', 'theory')->first()->id;
                $query->where('trainingLevel_id', $traingingLevel)
                    ->where('status', '!=', 'Finished');
            })
            ->when($status === 'practical', function ($query) {
                $traingingLevel = TrainingLevel::where('name', 'practical')->first()->id;
                $query->where('trainingLevel_id', $traingingLevel)
                    ->where('status', '!=', 'Finished');
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
                    ->orWhere('phone', 'like', "%$search%")
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
            ->addColumn('level', function ($student) {
                return '<div class="text-capitalize">' . ($student->trainingLevel ? $student->trainingLevel->name : 'Not assigned') . '</div>';
            })
            ->addColumn('car_assigned', function ($student) {
                if ($student->fleet) {
                    return $student->fleet->car_registration_number;
                } elseif ($student->classroom) {
                    return $student->classroom->name;
                } else {
                    return 'Not assigned';
                }
            })->addColumn('attendance', function ($student) {
                $attendanceCount = $student->attendance ? $student->attendance->count() : 0;
                $courseDuration = $student->course->duration ?? 0;
                $percentage = ($courseDuration > 0) ? number_format(($attendanceCount / $courseDuration) * 100, 1) : 0;

                $text = "{$attendanceCount} of {$courseDuration} ";

                if ($percentage >= 100) {
                    $badge = '<span class="badge bg-success">Completed</span>';
                } elseif ($percentage >= 50) {
                    $badge = '<span class="badge bg-info">' . $percentage . '%</span>';
                } else {
                    $badge = '<span class="badge bg-warning">' . $percentage . '%</span>';
                }

                return $text . '<br>' . $badge;
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
                            <i class="fa fa-user me-2"></i> View
                        </a>';

                $edit = '';
                $delete = '';

                $changeStatus = "<button
                                    class='dropdown-item change-status-btn'
                                    data-fname=\"" . e($student->fname) . "\"
                                    data-mname=\"" . e($student->mname) . "\"
                                    data-sname=\"" . e($student->sname) . "\"
                                    data-id=\"" . e($student->id) . "\"
                                    data-status=\"" . e($student->status) . "\">
                                        <i class='fas fa-toggle-on me-2'></i> Change status
                                </button>";

                if (auth()->user()->hasRole('superAdmin')) {
                    $edit = '<a class="dropdown-item" href="' . url('/edit-student', $student->id) . '">
                                <i class="fa fa-pencil me-2"></i> Edit
                            </a>';

                    $delete = '<form method="POST" action="' . url('student-delete', $student->id) . '" style="display:inline;">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="dropdown-item delete-confirm">
                                        <i class="fa fa-trash me-2"></i> Delete
                                    </button>
                                </form>';
                }

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $view . $edit . $delete . $changeStatus . '
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['actions', 'full_name', 'attendance', 'balance', 'course_status', 'level']) // allow HTML in 'actions'
            ->make(true);
    }

    public function fetchInstructorStudents(Request $request): JsonResponse
    {
        $search = $request->input('search.value');
        $status = $request->status;
        $instructorId = $request->instructorId;

        $instructor = Instructor::find($instructorId);

        if (!$instructor) {
            return response()->json(['error' => 'No students found.'], 404);
        }

        // Decide which relationships to eager load based on department
        $relations = ['user', 'course', 'invoice'];
        if ($instructor->department?->name === 'practical') {
            $relations[] = 'fleet';
        } elseif ($instructor->department?->name === 'theory') {
            $relations[] = 'classroom';
        }

        $students = Student::with($relations)
            ->when($status === 'active', function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('fleet_id')->orWhereNotNull('classroom_id');
                })->where('status', '!=', 'Finished');
            })
            ->when($status === 'unassigned', function ($query) {
                $query->whereNull('fleet_id')->whereNull('classroom_id')->where('status', '!=', 'Finished');
            })
            ->when($status === 'finished', function ($query) {
                $query->where('status', 'Finished');
            })
            ->orderBy('created_at', 'desc');

        // Apply instructor-specific filtering
        if ($instructor->department) {
            $departmentName = $instructor->department->name;

            switch ($departmentName) {
                case 'practical':
                    $fleetAssigned = Fleet::where('instructor_id', $instructor->id)->first();
                    if ($fleetAssigned) {
                        $students->where('fleet_id', $fleetAssigned->id);
                    } else {
                        throw new ModelNotFoundException(__('Instructor not allocated a car.'));
                    }
                    break;

                case 'theory':
                    $classroomIds = $instructor->classrooms->pluck('id');
                    if ($classroomIds->isNotEmpty()) {
                        $students->whereIn('classroom_id', $classroomIds);
                    } else {
                        throw new ModelNotFoundException(__('Instructor not allocated classroom.'));
                    }
                    break;
            }
        }

        if ($search) {
            $students->where(function ($query) use ($search) {
                $query->where('fname', 'like', "%$search%")
                    ->orWhere('mname', 'like', "%$search%")
                    ->orWhere('sname', 'like', "%$search%")
                    ->orWhereHas('course', function ($q) use ($search) {
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
                return '<strong><span class="' . $balanceClass . '">K' . number_format($invoiceBalance, 2) . '</span></strong>';
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

                if ($courseDuration <= 0) {
                    return '<span class="text-danger">No course info</span>';
                }

                $percentage = number_format(($attendanceCount / $courseDuration) * 100, 1);
                $text = "{$attendanceCount} of {$courseDuration} ";

                if ($percentage >= 100) {
                    $badge = '<span class="badge bg-success">Completed</span>';
                } elseif ($percentage >= 50) {
                    $badge = '<span class="badge bg-info">' . $percentage . '%</span>';
                } else {
                    $badge = '<span class="badge bg-warning">' . $percentage . '%</span>';
                }

                return $text . $badge;
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
                            ' . $view . $edit . $delete . $changeStatus . '
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['actions', 'full_name', 'attendance', 'balance', 'course_status'])
            ->make(true);
    }

    public function index()
    {
        try {
            $fleet = Fleet::all();
            $students = Student::with('User')->latest('created_at');

            if (Auth::user()->hasRole('instructor')) {
                $instructor = Auth::user()->instructor()->with(['department', 'classrooms'])->first();

                if ($instructor && $instructor->department) {
                    $departmentName = $instructor->department->name;

                    if ($departmentName === 'practical') {
                        $fleetAssigned = Fleet::where('instructor_id', $instructor->id)->first();
                        if ($fleetAssigned) {
                            $students->where('fleet_id', $fleetAssigned->id);
                        } else {
                            throw new ModelNotFoundException(__('You are not allocated a car.'));
                        }
                    }

                    if ($departmentName === 'theory') {
                        $classroomIds = $instructor->classrooms->pluck('id');
                        if ($classroomIds->isNotEmpty()) {
                            $students->whereIn('classroom_id', $classroomIds);
                        } else {
                            throw new ModelNotFoundException(__('You are not allocated a class room.'));
                        }
                    }
                }
            }

            $students = $students->get();

            $theoryCount = Student::whereHas('Course', function ($query) {
                $query->where('trainingLevel_id', TrainingLevel::where('name', 'theory')->first()->id);
            })->count();

            $practicalCount = Student::whereHas('Course', function ($query) {
                $query->where('trainingLevel_id', TrainingLevel::where('name', 'practical')->first()->id);
            })->count();

            return view('students.students', compact('students', 'fleet', 'theoryCount', 'practicalCount'));

        } catch (ModelNotFoundException $e) {
            Alert::error(__('No students'), $e->getMessage());
            Log::error($e);
            return redirect('/');
        } catch (\Exception $e) {
            Alert::error(__('Error'), __('An unexpected error occurred.'));
            Log::error($e);
            return redirect('/');
        }
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(!Auth::user()->hasRole('instructor')){
            $district = district::get();
            return view('students.addstudent', compact('district'));
        }

        abort(403);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreStudentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreStudentRequest $request)
    {
        // Custom error messages
        $messages = [
            'username.required' => 'Username is required!',
            'username.unique' => 'Username already taken!',
            'fname.required' => 'Firstname is required!',
            'sname.required' => 'Sirname is required!',
            'email.required' => 'Email address is required!',
            'email.unique' => 'Email address is already in use',
            'gender.required' => 'The "Gender" field is required!',
            'trn.unique' => 'TRN must be unique!',
            'date_of_birth.required' => 'Date of birth is required',
            'signature.required' => 'Signature is required',
            'signature.image' => 'Signature must be an image',
        ];

        // Validation rules
        $rules = [
            'username' => 'required|unique:users,name',
            'fname' => 'required',
            'sname' => 'required',
            'email' => 'required|unique:users',
            'address' => 'required',
            'gender' => 'required',
            'date_of_birth' => 'required',
            'district' => 'required',
            'phone' => 'required|unique:students',
            'trn' => 'unique:students',
            'signature' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        // Validate the request
        $validatedData = $request->validate($rules, $messages);

        $post = $request->all();

        // Select district using havenUtils
        $district = havenUtils::selectDistrict($post['district']);

        // Create new student
        $student = new Student;

        $user = Auth::user();

        $admin = Administrator::where('id', $user->administrator_id)->firstOrFail();

        // Signature processing
        if ($request->file('signature')) {
            $signatureName = $request->file('signature')->getClientOriginalName();
            $request->signature->move(public_path('media/signatures'), $signatureName);
            $student->signature = $signatureName;
        }

        $trainingLevel = havenUtils::trainingLevelID('registered');

        $student->fname = $post['fname'];
        $student->mname = $post['mname'];
        $student->sname = $post['sname'];
        $student->gender = $post['gender'];
        $student->trn = $post['trn'];
        $student->phone = $post['phone'];
        $student->address = $post['address'];
        $student->date_of_birth = $post['date_of_birth'];
        $student->district_id = $district;
        if ($trainingLevel) {
            $student->trainingLevel_id = $trainingLevel;
        }
        $student->added_by = $user->administrator_id;

        $student->save();

        // Create new user
        $user = new User;
        $user->name = $post['username']; //Str::random(10);
        $user->student_id = $student->id;
        $user->email = $post['email'];
        $user->password = bcrypt(Str::random(10)); // Encrypt the password

        $user->save();

        $user->assignRole('student');

        $superAdmins = User::role('superAdmin')->get();

        foreach ($superAdmins as $superAdmin) {
            $superAdmin->notify(new StudentRegistered($student, $admin->fname.' '.$admin->sname));
        }

        // Send notification SMS
        $sms = new NotificationController;
        $sms->balanceSMS($student->id, 'Registration');

        // Get the last student ID and show success message
        $studentLastID = Student::max('id');
        Alert::toast('Student ' . $student->fname . ' added successfully', 'success');

        return redirect()->route('viewStudent', ['id' => $studentLastID]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $student = Student::With('User', 'Course', 'Enrollment', 'Invoice', 'Payment', 'Classroom')->find($id);

        if(!isset($student)){
            abort(404);
        }

        havenUtils::checkStudentInstructor($id);

        $courseId = optional($student->course)->id;
        $courseDuration = $courseId ? havenUtils::courseDuration($courseId) : 0;

        $attendancePercent = havenUtils::attendancePercent($id)['attendancePercent'] ?? 0;

        $attendanceTheoryCount = havenUtils::attendanceByDepartment($id, 'd9b69664-b8ca-11ef-9fee-525400adf70e');
        $attendancePracticalCount = havenUtils::attendanceByDepartment($id, 'd9b6a9c9-b8ca-11ef-9fee-525400adf70e');

        if (request()->wantsJson()) {
            return response()->json(compact(
                'student',
                'attendancePercent',
                'courseDuration',
                'attendanceTheoryCount',
                'attendancePracticalCount'
            ));
        }

        return view('students.viewstudent', compact(
            'student',
            'attendancePercent',
            'courseDuration',
            'attendanceTheoryCount',
            'attendancePracticalCount'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $student = Student::with('User')->find($id);

        if(!isset($student) || !Auth::user()->hasRole('superAdmin')){
            abort(404);
        }

        $district = district::get();
        return view('students.editstudent', [ 'student' => $student ], compact('student', 'district'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateStudentRequest  $request
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        $post = $request->All();

        $messages = [
            'fname.required' => 'First name is required!',
            'sname.required'   => 'Sir name is required!',
            'email.required' => 'Email address is required!',
            'gender.required'   => 'Gender is required!',
            'phone.required'   => 'Gender is required!',
        ];

        // Validate the request
        $this->validate($request, [
            'fname'  =>'required',
            'sname' =>'required',
            'email'   =>'required',
            'address' =>'required',
            'gender'  =>'required',
            'date_of_birth' =>'required',
            'district' =>'required',
            'phone' =>'required'

        ], $messages);

        $student = Student::find($post['student_id']);
        $district = havenUtils::selectDistrict($post['district']);

        //signature processing
        if($request->file('signature')){
            $signatureName = $request->file('signature')->getClientOriginalName();
            $request->signature->move(public_path('media/signatures'), $signatureName);
            $student->signature = $signatureName;
        }

        $student->fname = $post['fname'];
        $student->mname = $post['mname'];
        $student->sname = $post['sname'];
        $student->gender = $post['gender'];
        $student->trn = $post['trn'];
        $student->phone = $post['phone'];
        $student->address = $post['address'];
        $student->date_of_birth = $post['date_of_birth'];
        $student->district_id = $district;

        $user = User::where('student_id', $post['student_id'])->firstOrFail();

        $user->email = $post['email'];

        $student->save();
        $user->save();

        Alert::toast('Student updated successifully', 'success');

        return redirect()->route('viewStudent', ['id' => $post['student_id']]);
    }

    public function updateStudentStatus(UpdateStudentRequest $request, $student)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Finished,In progress,Pending',
        ], [
            'status.required' => 'Status is required!',
        ]);

        $student = Student::findOrFail($student);
        $student->status = $validated['status'];
        if($validated['status'] == 'Finished'){
            $student->trainingLevel_id = havenUtils::trainingLevelID('finished');
            $student->fleet_id = Null;
            $student->classroom_id = Null;
        }
        else{
            $student->trainingLevel_id = havenUtils::trainingLevelID('registered');
        }

        $student->save();

        return response()->json([
            'message' => 'Student status updated to ' . $validated['status'],
            'status' => $validated['status']
        ]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $student = Student::find($id);
        $invoicelist = Invoice::where('student_id', $student->id)->get();
        $invoiceCount = $invoicelist->count();

        if($invoiceCount >= 1){

            Alert()->error('Student not deleted','There are invoices associated with the student, delete them first');
        }

        else{

            $student->delete();

            Alert::toast('Student deleted', 'success');
        }


        return back();
    }

    public function trafficCardReferenceLetter($id)
    {
        $student = Student::find($id);
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/'.$id);

        $pdf = PDF::loadView('pdf_templates.trafficCardReferenceLetter', compact('student', 'setting', 'date', 'qrCode'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Trafic Card Reference Letter.pdf');
    }

    public function aptitudeTestReferenceLetter($id)
    {
        $student = Student::find($id);
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/'.$id);

        $pdf = PDF::loadView('pdf_templates.aptitudeTestreferenceLetter', compact('student', 'setting', 'date', 'qrCode'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Highway Code I Reference Letter.pdf');
    }

    public function secondAptitudeTestReferenceLetter($id)
    {
        $student = Student::find($id);
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/'.$id);

        $pdf = PDF::loadView('pdf_templates.secondAptitudeTestreferenceLetter', compact('student', 'setting', 'date', 'qrCode'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Highway Code II Reference Letter.pdf');
    }

    public function finalReferenceLetter($id)
    {
        $student = Student::find($id);
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/'.$id);

        $pdf = PDF::loadView('pdf_templates.finalReferenceLetter', compact('student', 'setting', 'date', 'qrCode'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Final Reference Letter.pdf');
    }

    public function lessonReport($id)
    {
        $student = Student::find($id);
        $attendance = Attendance::where('student_id', $id)->orderBy('attendance_date', 'ASC')->get();
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/'.$id);

        $pdf = PDF::loadView('pdf_templates.lessonReport', compact('student', 'attendance', 'setting', 'date', 'qrCode'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Lesson Report.pdf');
    }

    public function studentsPDF(PdfStudentRequest $request)
    {
         $messages = [
            'Something is wrong PDF cant download!',
         ];

        $validator = Validator::make($request->all(), [
            'date' => [
                'required',
                Rule::in(['all_time']),
            ],

            'balance' => [
                'required',
                Rule::in(['all', 'balance', 'no_balance']),
            ],

            'status' => [
                'required',
                Rule::in(['allstatus', 'inprogress', 'finished']),
            ],
        ]);

        if ($validator->fails()) {

            Alert::toast($messages, 'Error');
            return back();

         }



        $balance = $request['balance'];

        if($request['status'] == 'allstatus'){
            $status = ['inprogress', 'finished', 'pending', 'suspended'];
        }

        else{
            $status = $request['status'];
        }

        $fleet_id = havenUtils::fleetID($request['fleet']);
        if(isset($fleet_id)){
            $fleet = Fleet::find($fleet_id);
            $fleet_number = $fleet->car_registration_number;
        }

        else{
            $fleet = null;
            $fleet_number = null;
        }

        switch($balance){
            case('balance'):
                if(isset($fleet_id)){
                    $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                    ->WhereRelation('invoice','invoice_balance','>', 0)
                    ->Where('fleet_id', $fleet_id)
                    ->Where('status', $status)
                    ->orderBy('sname', 'ASC')->get();
                }
                else{
                    $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                    ->whereRelation('invoice','invoice_balance','>', 0)
                    ->where('status', $status)
                    ->orderBy('sname', 'ASC')->get();
                }
            break;

            case('no_balance'):
                if(isset($fleet_id)){
                        $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                        ->WhereRelation('invoice','invoice_balance','=', 0)
                        ->Where('status', $status)
                        ->Where('fleet_id', $fleet_id)
                        ->orderBy('sname', 'ASC')->get();
                }
                else{
                    $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                    ->WhereRelation('invoice','invoice_balance','=', 0)
                    ->Where('status', $status)
                    ->orderBy('sname', 'ASC')->get();
                }
            break;

            case('all'):
                if(isset($fleet_id)){
                        $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                        ->Where('fleet_id', $fleet_id)
                        ->Where('status', $status)
                        ->orderBy('sname', 'ASC')
                        ->get();
                }
                else{
                    $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                    ->Where('status', $status)
                    ->orderBy('sname', 'ASC')
                    ->get();
                }
            break;

            default:
                $student = Student::With('User', 'Invoice', 'Attendance')
                ->orderBy('sname', 'ASC')->get();

        }

        $date = date('j F, Y');
        $pdf = PDF::loadView('pdf_templates.studentsReport', compact('student', 'date', 'fleet', 'balance'))->setOption(['dpi' => 150, 'defaultFont' => 'sans-serif'])->setPaper('a4', 'potrait');
        return $pdf->download('Daron Driving School -'.$fleet_number.' Students Report.pdf');
    }

    public function search(Request $request){


        if(Auth::user()->hasRole('instructor')){
            $fleet_id = Fleet::Where('instructor_id', Auth::user()->instructor_id)->firstOrFail()->id;
            $fleet = Fleet::Where('instructor_id', Auth::user()->instructor_id)->get();
            $activeStudents = Student::with('User')
            ->Where('fleet_id', $fleet_id)
            ->where(function ($query) {$query
                ->Where('fname', 'like', '%' . request('search') . '%')
                ->orWhere('mname', 'like', '%' . request('search') . '%')
                ->orWhere('sname', 'like', '%' . request('search') . '%')
                ->orWhere('phone', 'like', '%' . request('search') . '%')
                ->orWhere('trn', 'like', '%' . request('search') . '%')
                ->orwhereHas('User', function($q){$q->where('email','like', '%' . request('search') . '%');})->orderBy('fname', 'ASC');}
            )->paginate(20);
        }
        else{
            $fleet = Fleet::get();
            $activeStudents = Student::with('User')
                ->where('fname', 'like', '%' . request('search') . '%')
                ->orWhere('mname', 'like', '%' . request('search') . '%')
                ->orWhere('sname', 'like', '%' . request('search') . '%')
                ->orWhere('phone', 'like', '%' . request('search') . '%')
                ->orWhere('trn', 'like', '%' . request('search') . '%')
                ->orwhereHas('User', function($q){
                    $q->where('email','like', '%' . request('search') . '%');})->orderBy('fname', 'ASC')->paginate(20);
        }

        $finishedStudents = $activeStudents;

        return view('students.students', compact('activeStudents', 'fleet', 'finishedStudents'));
    }

    public function assignCar(Request $request, student $student)
    {
        $fleet = Fleet::with('instructor')->where('car_registration_number', $request['fleet'])->firstOrFail();

        $student = Student::find($request['student']);

        // Check if the student and fleet belong to the same licence class
        if (!$student->course || $student->course->licence_class_id !== $fleet->licence_class_id) {
            return response()->json('Student cannot be assigned to a different car class type', 403);
        }

        $student->fleet_id = $fleet->id;
        $student->classroom_id = Null;
        $student->trainingLevel_id = havenUtils::trainingLevelID('practical');
        $student->save();

        try {
            $sms = new NotificationController;
            $sms->generalSMS($student, 'Carassignment');

            if ($student->user) {
                $student->user->notify(new StudentCarAssigned($fleet, 'assign'));
            }
        } catch (\Throwable $e) {
            \Log::error('Car assignment notification failed: ' . $e->getMessage(), [
                'student_id' => $student->id ?? null,
                'fleet_id' => $fleet->id ?? null,
        ]);

            // Optionally return a response or silently continue
            // return response()->json(['error' => 'Notification failed'], 500);
        }


        if(!$student->save()){
            return response()->json('Something wrong happened', 403);
        }

        return response()->json('Success, student assigned car', 200);

    }

    public function unAssignCar(Request $request, student $student)
    {
        $student = Student::with('fleet')->find($request['student']);
        $fleet = $student->fleet;
        $student->fleet_id = null;
        $student->save();


        $student->user->notify(new StudentCarAssigned($fleet, 'un-assign'));


        if(!$student->save()){
            return response()->json('Something wrong happened', 403);
        }

        return response()->json('Success, student un assigned car', 200);

    }

    public function assignClassRoom(Request $request)
    {
        $messages = [
            'classroom.required' => 'The classroom field is required.',
            'classroom.exists'   => 'The selected classroom does not exist.',
            'student.required'   => 'The student field is required.',
            'student.exists'     => 'The selected student does not exist.',
        ];

        // Validate the request
        $this->validate($request, [
            'classroom' => 'required|exists:classrooms,id',
            'student'   => 'required|exists:students,id',
        ], $messages);

        try {
            // Find the student
            $student = Student::findOrFail($request->student);

            // Assign the classroom
            $student->classroom_id = $request->classroom;
            $student->fleet_id = Null;
            $student->trainingLevel_id = havenUtils::trainingLevelID('theory');
            $student->save();

            $classRoom = Classroom::with('instructors')->find($student->classroom_id);


            //$sms = new NotificationController;
            //$sms->generalSMS($student, 'Carassignment');

            $student->user->notify(new StudentClassAssignment($classRoom, $student));

            // Notify success
            return response()->json('Success, student assigned to classroom', 200);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

}
