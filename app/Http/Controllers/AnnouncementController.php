<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Jobs\SendAnnouncementSMS;
use App\Models\Instructor;
use App\Models\notification_template;
use App\Models\SmsReport;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;
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
        $today = Carbon::now();
        $group = $request->group;
        $body  = is_array($request->body) ? $request->body[0] : $request->body;

        // Base student query
        $studentsQuery = Student::query()
            ->where('status', '!=', 'Finished');

        /*
        |--------------------------------------------------------------------------
        | Instructor Filter (Optional)
        |--------------------------------------------------------------------------
        */
        if ($request->filled('instructor')) {

            $instructor = Instructor::with(['fleet', 'classrooms'])
                ->find($request->instructor);

            if (!$instructor) {
                return response()->json(['message' => 'Instructor not found'], 404);
            }

            $studentsQuery->where(function ($q) use ($instructor) {
                if ($instructor->fleet) {
                    $q->where('fleet_id', $instructor->fleet->id);
                }

                if ($instructor->classroom) {
                    $q->orWhere('classroom_id', $instructor->classroom->id);
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Group Filter
        |--------------------------------------------------------------------------
        */
        switch ($group) {

            case 'All students':
                // No extra filtering
                break;

            case 'Students with balance':
                $studentsQuery->whereHas('invoice', function ($query) use ($today) {
                    $query->where('invoice_balance', '!=', 0)
                        ->whereDate('invoice_payment_due_date', '<', $today);
                });
                break;

            default:
                return response()->json(['message' => 'Invalid group selected'], 422);
        }

        $students = $studentsQuery->with(['invoice', 'course'])->get();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No students found'], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Send Messages
        |--------------------------------------------------------------------------
        */
        $sent = 0;

        foreach ($students as $student) {

            $report = SmsReport::create([
                'student_id'    => $student->id,
                'instructor_id' => $request->instructor,
                'phone'         => $student->phone,
                'message'       => $body,
                'status'        => 'queued'
            ]);

            SendAnnouncementSMS::dispatch($student, $body, $report->id)
                ->onQueue('instructor_' . ($request->instructor ?? 'general'));
        }

        return response()->json([
            'message' => 'Announcement queued successfully',
            'recipients' => $students->count()
        ], 200);
    }

}
