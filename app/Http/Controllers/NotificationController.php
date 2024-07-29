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
    public function sendSMS(student $student)
    {
        if (! $this->middleware(['role:superAdmin|admin'])) {
            abort(403, 'Unauthorized action.');
        }

        $student = Student::with('User', 'Invoice')->find($student->id);
        $total = number_format($student->invoice->invoice_total, 2);
        $paid = number_format($student->invoice->invoice_amount_paid, 2);
        $balance = number_format($student->invoice->invoice_balance, 2);
        $due_date = $student->invoice->invoice_payment_due_date->format('j F, Y');

        $variables = array("first_name"=>$student->fname,"middle_name"=>$student->mname,"sir_name"=>$student->sname,"invoice_total"=>$total, "invoice_paid"=>$paid, "balance"=>$balance, "due_date"=>$due_date);

        $sms_template = notification_template::where('type', 'new')->firstOrFail()->body;

        foreach($variables as $key => $value){
            $sms_template = str_replace('{'.strtoupper($key).'}', $value, $sms_template);
        }

        $destination = $student->phone;
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
                            'message' => $sms_template
                        ])
                    ]);

            $statusCode = $response->getStatusCode();
            $responseMessage = 'SMS sent';//$response->getBody();
            // Process the response as needed
        } catch (\Exception $e) {
            // Handle the exception
            $responseMessage = 'SMS not sent, something happened';
        }

        Alert::toast($responseMessage, 'success');
        return back();

    }

}
