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
        $this->middleware(['role:superAdmin'], ['role:admin']);
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
    public function sendSMS($id)
    {
        if (! $this->middleware(['role:admin'])) {
            abort(403, 'Unauthorized action.');
        }

        $student = Student::with('User', 'Invoice')->where('id', $id)->firstOrFail();
        $total = number_format($student->invoice->invoice_total, 2);
        $paid = number_format($student->invoice->invoice_amount_paid, 2);
        $balance = number_format($student->invoice->invoice_balance, 2);
        $due_date = $student->invoice->invoice_payment_due_date->format('j F, Y');

        $variables = array("first_name"=>$student->fname,"middle_name"=>$student->mname,"sir_name"=>$student->sname,"invoice_total"=>$total, "invoice_paid"=>$paid, "balance"=>$balance, "due_date"=>$due_date);

        $sms_template = notification_template::where('type', 'new')->firstOrFail()->body;;

        $string = $sms_template;

        foreach($variables as $key => $value){
            $string = str_replace('{'.strtoupper($key).'}', $value, $string);
        }

        $destination = $student->phone;
        $source = "Daron DS";

        $client = new Client();

        $response = $client->post('http://api.rmlconnect.net/bulksms/bulksms?username=haventechno&password=08521hav&type=0&dlr=0&destination='.$destination.'&source='.$source.'&message='.$string);

        Alert::toast('SMS sent succesifully', 'success');
        return back();

    }

}
