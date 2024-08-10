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
        $students = Student::where('status', '!=', 'Finished')->get();

        foreach($students as $student){
            $sendSMS = new NotificationController;
            $response = $sendSMS->sendSMS($post['body'], '0996884869');
        }

        return response()->json($response['message'], $response['statusCode']);
    }
}
