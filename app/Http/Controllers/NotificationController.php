<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Student;
use App\Models\notification_template;
use App\Models\Invoice;
use App\Models\Account;
use App\Models\User;
use App\Models\District;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\Setting;
use SebastianBergmann\Template\Template;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:superAdmin|admin']);
    }
    /**
     * Update the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateSMSTemplates()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createSMSTemplate()
    {
        $templates = notification_template::get();
        return view('sms_templates', compact('templates'));
    }


    /**
     * Send the sms.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendSMS($sms_body, $destination)
    {
        if (! $this->middleware(['role:superAdmin|admin'])) {
            abort(403, 'Unauthorized action.');
        }

        $source = env('SMS_SENDER_ID');

        $client = new Client();

        try {
            $response = $client->post(env('SMS_URL'), [
                'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . env('SMS_AUTH_KEY')
            ],
            'body' => json_encode([
                            'from' => $source,
                            'to' => $destination,
                            'message' => $sms_body
                        ])
                    ]);

            $statusCode = $response->getStatusCode();
            $response = [
                'statusCode' => $statusCode,
                'message' => 'SMS sent successfully'
            ];
            // Process the response as needed
        } catch (\Exception $e) {
            // Handle the exception
            $response = [
                'statusCode' => $e,
                'message' => 'SMS not sent, something went wrong!'
            ];

            return $response;
        }

        return $response;

    }

    //send sms for enrollment, payments and balance reminders
    public function balanceSMS(student $student, $type){

        $destination = $student->phone;
        $student = Student::with('User', 'Invoice')->find($student->id);
        $course = $student->course ? $student->course: '';
        $total = $student->invoice ? number_format($student->invoice->invoice_total, 2, '.', '') : '';
        $paid = $student->invoice ? number_format($student->invoice->invoice_amount_paid, 2, '.', '') : '';
        $balance = $student->invoice ? number_format($student->invoice->invoice_balance, 2, '.', '') : '';
        $due_date =  $student->invoice ? $student->invoice->invoice_payment_due_date->format('j F, Y'): '';
        //$type = $student['type'];

        $variables = array(
            "first_name"=>$student->fname,
            "middle_name"=>$student->mname,
            "sir_name"=>$student->sname,
            "invoice_total"=>$total,
            "invoice_paid"=>$paid,
            "balance"=>$balance,
            "due_date"=>$due_date,
            "course_name"=> $student->course ? $course->name : '',
        );

        $sms_template = notification_template::where('type', $type)->firstOrFail()->body;

        foreach($variables as $key => $value){
            $sms_template = str_replace('{'.strtoupper($key).'}', $value, $sms_template);
        }

        $response = $this->sendSMS($sms_template, $destination);

        if($response['statusCode'] == '200'){
            Alert::toast($response['message'], 'success');
        }
        else{
            Alert::toast($response['message'], 'error');
        }

        return back();

    }

    public function announcementSMS(){

    }

    public function attendanceSMS($student){

        $destination = $student->phone;
        $student = Student::with('User', 'Invoice')->find($student->id);
        $attendanceRequired = $student->course->practicals + $student->course->theory;
        $attendanceCount = $student->attendance->count();
        $attendance_balance = $attendanceRequired - $attendanceCount;

        $attendanceLatest = $student->attendance()
        ->orderBy('created_by', 'DESC')
        ->firstOrFail()->created_at;


        $variables = array(
            "first_name"=>$student->fname,
            "middle_name"=>$student->mname,
            "sir_name"=>$student->sname,
            "total_attendance_entered"=>$attendanceCount,
            "attendance_difference"=>$attendance_balance,
            "total_required_attendance"=>$attendanceRequired,
            "attendance_date"=>$attendanceLatest
        );

        $sms_template = notification_template::where('type', 'Attendance')->firstOrFail()->body;

        foreach($variables as $key => $value){
            $sms_template = str_replace('{'.strtoupper($key).'}', $value, $sms_template);
        }

        $response = $this->sendSMS($sms_template, $destination);

        if($response['statusCode'] == '200'){
            Alert::toast($response['message'], 'success');
        }
        else{
            Alert::toast($response['message'], 'error');
        }

        return back();
    }

}
