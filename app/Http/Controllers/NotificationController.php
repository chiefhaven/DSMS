<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Account;
use App\Models\User;
use App\Models\District;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\Setting;

class NotificationController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendSMS($id)
    {
        $student = Student::with('User', 'Invoice')->where('id', $id)->firstOrFail();
        $balance = number_format($student->invoice->invoice_balance, 2);
        $due_date = $student->invoice->invoice_payment_due_date->format('j F, Y');
        $sms_body = 'Dear '.$student->fname.' '.$student->sname.', you have a balance of K'.$balance.' from Daron Driving School due '.$due_date.'. Kindly pay as soon as possible. For more information Call/WhatsApp 0999532688. Best regards!';
        $destination = $student->phone;
        $source = "Daron DS";

        $client = new Client();

        $response = $client->post('http://api.rmlconnect.net/bulksms/bulksms?username=haventechno&password=08521hav&type=0&dlr=0&destination='.$destination.'&source=Daron DS&message='.$sms_body);

        Alert::toast('SMS sent succifully', 'success');
        return back();

    }

}
