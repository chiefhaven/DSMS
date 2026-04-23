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
            ])->post(config('services.smsApi.url') . '/sms/send', [
                'message'    => $sms_body,
                'type'       => 'plain',
                'sender_id'  => config('services.smsApi.from'),
                'recipient' => $destination,
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
        $student = Student::with(['user', 'invoice', 'course'])->find($studentId);

        if (!$student) {
            Alert::toast('Student not found', 'error');
            return back();
        }

        if (empty($student->phone)) {
            Alert::toast('Student phone number is missing', 'error');
            return back();
        }

        $invoice = $student->invoice;
        $course  = $student->course;

        // Handle missing invoice
        if (!$invoice) {
            Alert::toast('No invoice found for this student', 'error');
            return back();
        }

        $balance = (float) $invoice->invoice_balance;

        // Check if balance is zero
        if ($balance <= 0) {
            Alert::toast('No outstanding balance to remind.', 'info');
            return back();
        }

        $variables = [
            "first_name"    => $student->fname ?? '',
            "middle_name"   => $student->mname ?? '',
            "sir_name"      => $student->sname ?? '',
            "invoice_total" => number_format($invoice->invoice_total, 2),
            "invoice_paid"  => number_format($invoice->invoice_amount_paid, 2),
            "balance"       => number_format($invoice->invoice_balance, 2),
            "due_date"      => $invoice->invoice_payment_due_date
                                ? $invoice->invoice_payment_due_date->format('j F, Y')
                                : 'N/A',
            "course_name"   => $course->name ?? 'N/A',
        ];

        $template = notification_template::where('type', $type)->first();

        if (!$template) {
            Alert::toast('SMS template not found', 'error');
            return back();
        }

        $smsBody = $template->body;
        foreach ($variables as $key => $value) {
            $smsBody = str_replace('{' . strtoupper($key) . '}', $value, $smsBody);
        }

        $response = $this->sendSMS($smsBody, $student->phone);

        if (isset($response['statusCode']) && $response['statusCode'] == 200) {
            Alert::toast($response['message'] ?? 'SMS sent successfully', 'success');
        } else {
            $errorMessage = $response['message'] ?? 'Failed to send SMS';
            Alert::toast($errorMessage, 'error');
        }

        return back();
    }

    public function announcementSMS(){

    }

    public function generalSMS($student, string $type): \Illuminate\Http\RedirectResponse
    {
        $destination = $student->phone;

        $student = Student::with([
            'classroom',
            'user',
            'invoice',
            'course.lessons',
            'attendance.lesson',
            'fleet.instructor',
        ])->find($student->id);

        if (!$student || !$student->course) {
            Alert::toast('Student or course not found', 'error');
            return back();
        }

        $user        = Auth::user();
        $attendances = $student->attendance;

        $lessonDeptId = (int) $student->attendance->first()?->lesson?->department_id ?: null;
        $userDeptId   = (int) $user->instructor?->department_id ?: null;

        $isFilteredInstructor = $user->hasRole('instructor')
            && $userDeptId !== null
            && $lessonDeptId !== null
            && $userDeptId === $lessonDeptId;

        $lessons = $student->course->lessons;

        $attendanceRequired = $isFilteredInstructor
            ? (int) $lessons->where('department_id', $lessonDeptId)->sum('lesson_quantity')
            : (int) $lessons->sum('lesson_quantity');

        $attendanceCount   = $attendances->count();
        $attendance_balance = $attendanceRequired - $attendanceCount;

        Log::info('Attendance summary', [
            'required'  => $attendanceRequired,
            'count'     => $attendanceCount,
            'balance'   => $attendance_balance,
            'student_id' => $student->id,
        ]);

        $attendanceLatest = $attendances->isNotEmpty()
            ? $attendances->sortByDesc('created_at')->first()->created_at->format('Y-m-d H:i:s')
            : '';

        $fleet     = $student->fleet;
        $classRoom = $student->classroom;
        $instructor = $fleet?->instructor;

        $variables = [
            'first_name'                => $student->fname  ?? '',
            'middle_name'               => $student->mname  ?? '',
            'sir_name'                  => $student->sname  ?? '',
            'total_attendance_entered'  => $attendanceCount,
            'attendance_difference'     => $attendance_balance,
            'total_required_attendance' => $attendanceRequired,
            'attendance_date'           => $attendanceLatest,
            'car_assigned'              => $fleet
                ? "{$fleet->car_brand_model}, {$fleet->car_registration_number}"
                : '',
            'class_room_assigned'       => $classRoom
                ? "{$classRoom->name}, {$classRoom->location}"
                : '',
            'instructor'                => $instructor
                ? "{$instructor->fname} {$instructor->mname} {$instructor->sname}, {$instructor->phone}"
                : '',
        ];

        $template = notification_template::where('type', $type)->first();

        if (!$template) {
            Alert::toast("SMS template '{$type}' not found", 'error');
            return back();
        }

        $body = $template->body;

        foreach ($variables as $key => $value) {
            $body = str_replace('{' . strtoupper($key) . '}', (string) $value, $body);
        }

        $response = $this->sendSMS($body, $destination);

        $status = $response['statusCode'] == '200' ? 'success' : 'error';
        Alert::toast($response['message'], $status);

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
