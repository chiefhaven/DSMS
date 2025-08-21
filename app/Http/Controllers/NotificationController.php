<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Student;
use App\Models\notification_template;
use App\Models\Setting;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware(['role:superAdmin|admin|student']);
    // }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('notifications/notifications');
    }


    /**
     * Display a listing of notifications.
     *
     * @return \Illuminate\Http\Response
     */
    public function loadNotifications(Request $request)
    {
        $user = Auth::user();

        // Get page number from query params (default 1)
        $page = $request->query('page', 1);

        // Define how many per page
        $perPage = 10;

        // Use the notifications relationship with pagination
        $notifications = $user->notifications()
                            ->orderBy('created_at', 'desc')
                            ->paginate($perPage, ['*'], 'page', $page);

        // Return only the collection data (items) as JSON
        return response()->json($notifications->items());
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

        try {
            $apiResponse = Http::withHeaders([
                'Authorization' => config('services.smsApi.token'),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->post(config('services.smsApi.url') . '/send-sms', [
                'to'      => $destination,
                'message' => $sms_body,
                'from'    => config('services.smsApi.from'),
            ]);

            if ($apiResponse->failed()) {
                Log::error("SMS Failed: " . $apiResponse->body());
                return [
                    'statusCode' => $apiResponse->status(),
                    'message'    => 'SMS sending failed',
                    'error'      => $apiResponse->body(),
                ];
            }

            Log::info("SMS Sent: " . $apiResponse->body());

            return [
                'statusCode' => $apiResponse->status(),
                'message'    => 'SMS sent successfully',
                'data'       => $apiResponse->json(),
            ];
        } catch (\Exception $e) {
            Log::error("SMS Exception: " . $e->getMessage());

            return [
                'statusCode' => 500,
                'message'    => 'Exception while sending SMS',
                'error'      => $e->getMessage(),
            ];
        }
    }

    // Send SMS for enrollment, payments, and balance reminders
    public function balanceSMS($studentId, $type)
    {
        $student = Student::with('User', 'Invoice', 'Course')->find($studentId);

        // Ensure all necessary relations are loaded
        if (!$student) {
            Alert::toast('Student not found', 'error');
            return back();
        }

        $destination = $student->phone;

        $course = $student->course;
        $total = $student->invoice ? number_format($student->invoice->invoice_total, 2, '.', '') : '';
        $paid = $student->invoice ? number_format($student->invoice->invoice_amount_paid, 2, '.', '') : '';
        $balance = $student->invoice ? number_format($student->invoice->invoice_balance, 2, '.', '') : '';
        $due_date = $student->invoice ? $student->invoice->invoice_payment_due_date->format('j F, Y') : '';

        $variables = [
            "first_name" => $student->fname ?? '',
            "middle_name" => $student->mname ?? '',
            "sir_name" => $student->sname ?? '',
            "invoice_total" => $total,
            "invoice_paid" => $paid,
            "balance" => $balance,
            "due_date" => $due_date,
            "course_name" => $student->course->name ?? '',
        ];

        $sms_template = notification_template::where('type', $type)->firstOrFail()->body;

        foreach ($variables as $key => $value) {
            $sms_template = str_replace('{' . strtoupper($key) . '}', $value, $sms_template);
        }

        $response = $this->sendSMS($sms_template, $destination);

        if ($response['statusCode'] == '200') {
            Alert::toast($response['message'], 'success');
        } else {
            Alert::toast($response['message'], 'error');
        }

        return back();
    }


    public function announcementSMS(){

    }

    public function generalSMS($student, $type)
    {
        $destination = $student->phone;
        $student = Student::with('Classroom','User', 'Invoice', 'course', 'attendance', 'fleet.instructor')->find($student->id);

        // Ensure all necessary relations are loaded
        if (!$student || !$student->course) {
            Alert::toast('Student or course not found', 'error');
            return back();
        }

        $attendanceRequired = $student->course->practicals + $student->course->theory;
        $attendanceCount = $student->attendance->count();
        $attendance_balance = $attendanceRequired - $attendanceCount;
        $fleet = $student->fleet;
        $classRoom = $student->classroom;

        $attendanceLatest = $student->attendance->isNotEmpty() ? $student->attendance()->orderBy('created_at', 'DESC')->first()->created_at->format('Y-m-d H:i:s') : '';

        $variables = [
            "first_name" => $student->fname,
            "middle_name" => $student->mname ?? '',
            "sir_name" => $student->sname,
            "total_attendance_entered" => $attendanceCount ?? '',
            "attendance_difference" => $attendance_balance,
            "total_required_attendance" => $attendanceRequired,
            "attendance_date" => $attendanceLatest,
            "car_assigned" => $fleet ? "{$fleet->car_brand_model}, {$fleet->car_registration_number}" : '',
            "class_room_assigned" => $classRoom ? "{$classRoom->name}, {$classRoom->location}" : '',
            "instructor" => $fleet ? "{$fleet->instructor->fname} {$fleet->instructor->mname} {$fleet->instructor->sname}, {$fleet->instructor->phone}" : '',
        ];

        $sms_template = notification_template::where('type', $type)->firstOrFail()->body;

        foreach ($variables as $key => $value) {
            $sms_template = str_replace('{' . strtoupper($key) . '}', $value, $sms_template);
        }

        $response = $this->sendSMS($sms_template, $destination);

        if ($response['statusCode'] == '200') {
            Alert::toast($response['message'], 'success');
        } else {
            Alert::toast($response['message'], 'error');
        }

        return back();
    }

    public function markAsRead(Request $request, $notificationId)
    {
        // Find the notification by ID
        $notification = Auth::user()->notifications()->findOrFail($notificationId);

        // Mark the notification as read
        $notification->markAsRead();

        if($request->api)
        {
            return response()->json(['status' => 'read']);
        }

        // Redirect to the URL stored in the notification data
        return redirect($notification->data['url'] ?? '/');
    }

    public function markAllRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        Alert::toast('All notifications marked as read.', 'success');

        return back()->with('status', 'All notifications marked as read.');
    }
}
