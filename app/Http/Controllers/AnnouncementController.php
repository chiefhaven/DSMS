<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\notification_template;
use App\Models\Student;
use App\Policies\NotificationTemplatePolicy;
use League\Uri\UriTemplate\Template;
use Twilio\Rest\Api\V2010\Account\Call\NotificationContext;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:superAdmin']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! $this->middleware(['role:superAdmin|admin'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('announcements.sendAnnouncement');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAnnouncementRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAnnouncementRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function show(Announcement $announcement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function edit(Announcement $announcement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAnnouncementRequest  $request
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function destroy(Announcement $announcement)
    {
        //
    }

    public function getBalanceTempplate()
    {
        $template = notification_template::find(3)->body;
        return response()->json([$template], 200);
    }

    public function send(Request $request)
    {
        $post = $request->all();
        $students = Student::where('status', 'Haven')->get();

        // Access the first element if it's an array
        if (is_array($post['body'])) {
            $body = $post['body'][0];
        } else {
            $body = $post['body']; // It's already a string
        }

        foreach($students as $student){

            $variables = [
                "first_name" => $student->fname ?? '',
                "middle_name" => $student->mname ?? '',
                "sir_name" => $student->sname ?? '',
                "invoice_total" => $student->invoice->total ?? '',
                "invoice_paid" => $student->invoice->paid ?? '',
                "balance" => $student->invoice->balance ?? '',
                "due_date" => $student->invoice->due_date ?? '',
                "course_name" => $student->course->name ?? '',
            ];

            foreach ($variables as $key => $value) {
                $sms_template = str_replace('{' . strtoupper($key) . '}', $value, $body);
            }

            $sendSMS = new NotificationController;
            $response = $sendSMS->sendSMS($sms_template, $student->phone);
        }

        return response()->json($response['message'], $response['statusCode']);
    }
}
