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
use App\Models\Fleet;
use Illuminate\Support\Str;
use PDF;
use RealRashid\SweetAlert\Facades\Alert;
use Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth::user()->hasRole('instructor')){

            try {
                $fleet = Fleet::where('instructor_id', Auth::user()->instructor_id)->firstOrFail();

                $student = Student::where('fleet_id', $fleet->id)
                    ->with('User', 'Attendance', 'Course')
                    ->orderBy('created_at', 'DESC')
                    ->paginate(10);

                return view('students.students', compact('student'));

            } catch (ModelNotFoundException $e) {
                Alert::error('No students', 'You are not allocated a car, for more information contact the admin');
                return redirect('/');
            }
        }
        else{
            $student = Student::with('User', 'Attendance', 'Course')->orderBy('created_at', 'DESC')->paginate(10);
        }

        $fleet = Fleet::get();
        return view('students.students', compact('student', 'fleet'));
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

        // Signature processing
        if ($request->file('signature')) {
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

        $student->save();

        // Create new user
        $user = new User;
        $user->name = Str::random(10);
        $user->student_id = $student->id;
        $user->email = $post['email'];
        $user->password = bcrypt(Str::random(10)); // Encrypt the password

        $user->save();

        // Send notification SMS
        $sms = new NotificationController;
        $sms->balanceSMS($student, 'Registration');

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
        $student = Student::With('User', 'Course', 'Enrollment', 'Invoice', 'Payment')->find($id);

        if(!isset($student)){
            abort(404);
        }

        havenUtils::checkStudentInstructor($id);

        $attendancePercent = havenUtils::attendancePercent($id);
        $attendanceTheoryCount = Attendance::where('student_id', $id)->where('lesson_id', 1)->count();
        $attendancePracticalCount = Attendance::where('student_id', $id)->where('lesson_id', 2)->count();
        return view('students.viewstudent', [ 'student' => $student ], compact('student', 'attendancePercent', 'attendanceTheoryCount', 'attendancePracticalCount'));
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
        $messages = [
            'fname.required' => 'First name is required!',
            'sname.required'   => 'Sir name is required!',
            'email.required' => 'Email is required!',
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

        $post = $request->All();

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

    public function updateStudentStatus(UpdateStudentRequest $request, Student $student)
    {
        $messages = [
            'status.required' => 'Status is required!',
        ];

        $this->validate($request, [
            'status' => 'required|string|in:Finished,In progress,Pending', // Adjust the rules as needed
        ], $messages);

        $post = $request->All();
        $student = Student::find( $student->id );
        $student->status = $post['status'];

        $student->save();

        Alert::toast('Student status updated to '. $post['status'] , 'success');
        return back();

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
            $student = Student::with('User')
            ->Where('fleet_id', $fleet_id)
            ->where(function ($query) {$query
                ->Where('fname', 'like', '%' . request('search') . '%')
                ->orWhere('mname', 'like', '%' . request('search') . '%')
                ->orWhere('sname', 'like', '%' . request('search') . '%')
                ->orWhere('phone', 'like', '%' . request('search') . '%')
                ->orWhere('trn', 'like', '%' . request('search') . '%')
                ->orwhereHas('User', function($q){$q->where('email','like', '%' . request('search') . '%');})->orderBy('fname', 'ASC');}
            )->paginate(10);
        }
        else{
            $fleet = Fleet::get();
            $student = Student::with('User')
                ->where('fname', 'like', '%' . request('search') . '%')
                ->orWhere('mname', 'like', '%' . request('search') . '%')
                ->orWhere('sname', 'like', '%' . request('search') . '%')
                ->orWhere('phone', 'like', '%' . request('search') . '%')
                ->orWhere('trn', 'like', '%' . request('search') . '%')
                ->orwhereHas('User', function($q){
                    $q->where('email','like', '%' . request('search') . '%');})->orderBy('fname', 'ASC')->paginate(10);
        }


        return view('students.students', compact('student', 'fleet'));
    }

    public function assignCar(Request $request, student $student)
    {
        $fleet_id = havenUtils::fleetID($request['fleet']);
        $student = Student::find($request['student']);
        $student->fleet_id = $fleet_id;
        $student->save();


        $sms = new NotificationController;
        $sms->generalSMS($student, 'Carassignment');

        if(!$student->save()){
            return response()->json('Something wrong happened', 403);
        }

        return response()->json('Success, student assigned car', 200);

    }
}
