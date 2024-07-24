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

        $sms = $sms_template;

        foreach($variables as $key => $value){
            $sms = str_replace('{'.strtoupper($key).'}', $value, $sms);
        }

        $destination = $student->phone;
        $source = "Daron DS";

        $client = new Client();

        try {
            $response = $client->post('https://clicksmsgateway.com', [
                'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiI0MTEiLCJvaWQiOjQxMSwidWlkIjoiMWNhNGE3YmYtYjA0Yy00NzgwLWJiZTMtYzI0N2IxNTA5MDhiIiwiYXBpZCI6MTY3LCJpYXQiOjE2NjkwMTM4NzUsImV4cCI6MjAwOTAxMzg3NX0.6CGWIPBH5Daa8BpWig_B1xVoHOmn4PPYRWpMe2KkZVn9Akhjh1mxfN3suWuOO1RW3MKmu6SE1i896fM1ugQpRg'
            ],
            'body' => json_encode([
                            'from' => $source,
                            'to' => $destination,
                            'message' => $sms
                        ])
                    ]);

            $statusCode = $response->getStatusCode();
            $responseMessage = $response->getBody();
            // Process the response as needed
        } catch (\Exception $e) {
            // Handle the exception
            $responseMessage = $e->getMessage();
        }

            Alert::toast('SMS sent succesifully', 'success');
        return back();

    }

}
