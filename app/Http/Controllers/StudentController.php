<?php

namespace App\Http\Controllers;
use App\Http\Controllers\havenUtils;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Account;
use App\Models\User;
use App\Models\District;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\Setting;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\PdfStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Fleet;
use Session;
use Illuminate\Support\Str;
use PDF;
use PhpParser\Node\Stmt\Switch_;
use RealRashid\SweetAlert\Facades\Alert;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $student = Student::with('User', 'Attendance', 'Course')->orderBy('created_at', 'DESC')->paginate(10);
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
        $district = district::get();
        return view('students.addstudent', compact('district'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreStudentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreStudentRequest $request)
    {
        $messages = [
            'fname.required' => 'The "First name" field is required!',
            'sname.required'   => 'The "Sir name" field is should be unique!',
            'email.required' => 'The "Email" is required!',
            'email.unique' => 'The "Email" is already in use',
            'gender.required'   => 'The "Gender" field required!',
            'trn.unique'   => 'This "TRN" is already registered!',
            'date_of_birth.required' => 'Date of birth is required',
            'signature.required' => 'Signature i required',
            'signature.image' => 'Signature must be an image',
        ];

        // Validate the request
        $this->validate($request, [
            'fname'  =>'required',
            'sname' =>'required',
            'email'   =>'required | unique:users',
            'address' =>'required',
            'gender'  =>'required',
            'date_of_birth' =>'required',
            'district' =>'required',
            'phone' =>'required',
            'trn' =>'unique:students',
            'signature' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ], $messages);

        $post = $request->All();

        $district = havenUtils::selectDistrict($post['district']);

        $student = new Student;

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

        $student->save();


        $user = new User;
        $user->name = Str::random(10);
        $user->student_id = $student->id;
        $user->email = $post['email'];
        $user->password = Str::random(10);

        $user->save();

        $studentLastID = Student::max('id');
        Alert::toast('Student'.' '.$student->fname.' '.'added successifully', 'success');
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
        $student = Student::with('User', 'Course', 'Enrollment', 'Invoice', 'Payment')->find($id);
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
            'fname.required' => 'The "First name" field is required!',
            'sname.required'   => 'The "Sir name" field is should be unique!',
            'email.required' => 'The "Email" is required!',
            'gender.required'   => 'The "Gender" field required!',
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $invoicelist = Invoice::where('student_id', $id)->get();
        $invoiceCount = $invoicelist->count();

        if($invoiceCount >= 1){

            Alert::toast('There are invoices associated with the student, delete them first', 'warning');
        }

        else{

            Student::find($id)->delete();
            User::where('student_id', $id)->delete();
            Attendance::where('student_id', $id)->delete();

            Alert::toast('Student deleted', 'success');
        }


        return redirect('/students');
    }


    public function trafficCardReferenceLetter($id)
    {
        $student = Student::find($id);
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $pdf = PDF::loadView('pdf_templates.trafficCardReferenceLetter', compact('student', 'setting', 'date'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Trafic Card Reference Letter.pdf');
    }

    public function aptitudeTestReferenceLetter($id)
    {
        $student = Student::find($id);
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $pdf = PDF::loadView('pdf_templates.aptitudeTestreferenceLetter', compact('student', 'setting', 'date'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Highway Code I Reference Letter.pdf');
    }

    public function secondAptitudeTestReferenceLetter($id)
    {
        $student = Student::find($id);
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $pdf = PDF::loadView('pdf_templates.secondAptitudeTestreferenceLetter', compact('student', 'setting', 'date'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Highway Code II Reference Letter.pdf');
    }

    public function finalReferenceLetter($id)
    {
        $student = Student::find($id);
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $pdf = PDF::loadView('pdf_templates.finalReferenceLetter', compact('student', 'setting', 'date'));
        return $pdf->download('Daron Driving School-'.$student->fname.' '.$student->sname.' Final Reference Letter.pdf');
    }

    public function lessonReport($id)
    {
        $student = Student::find($id);
        $attendance = Attendance::where('student_id', $id)->orderBy('attendance_date', 'ASC')->get();
        $setting = Setting::find(1);
        $date = date('j F, Y');

        $pdf = PDF::loadView('pdf_templates.lessonReport', compact('student', 'attendance', 'setting', 'date'));
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
            return redirect()->back();

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
                    ->whereRelation('invoice','invoice_balance','>', 0)
                    ->where('fleet_id', $fleet_id)
                    ->where('status', $status)
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
                        ->whereRelation('invoice','invoice_balance','=', 0)
                        ->where('status', $status)
                        ->where('fleet_id', $fleet_id)
                        ->orderBy('sname', 'ASC')->get();
                }
                else{
                    $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                    ->whereRelation('invoice','invoice_balance','=', 0)
                    ->where('status', $status)
                    ->orderBy('sname', 'ASC')->get();
                }
            break;

            case('all'):
                if(isset($fleet_id)){
                        $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                        ->where('fleet_id', $fleet_id)
                        ->where('status', $status)
                        ->orderBy('sname', 'ASC')
                        ->get();
                }
                else{
                    $student = Student::With('User', 'Invoice', 'Attendance', 'Fleet')
                    ->where('status', $status)
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


        $fleet = Fleet::get();

        $student = Student::with('User')
                ->where('fname', 'like', '%' . request('search') . '%')
                ->orWhere('mname', 'like', '%' . request('search') . '%')
                ->orWhere('sname', 'like', '%' . request('search') . '%')
                ->orWhere('phone', 'like', '%' . request('search') . '%')
                ->orWhere('trn', 'like', '%' . request('search') . '%')
                ->orwhereHas('User', function($q){
                    $q->where('email','like', '%' . request('search') . '%');})->paginate(10);

        return view('students.students', compact('student', 'fleet'));
    }
}
