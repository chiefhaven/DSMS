<?php

namespace App\Http\Controllers;

use App\Models\bulkAttendance;
use App\Http\Requests\StorebulkAttendanceRequest;
use App\Http\Requests\UpdatebulkAttendanceRequest;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use DB;
use Illuminate\Support\Facades\Log;
use Auth;

class BulkAttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search.value');

        $bulkAttendances = bulkAttendance::with('administrator', 'students')
            ->orderBy('created_at', 'DESC');

        if ($search) {
            $bulkAttendances->where(function($query) use ($search) {
                $query->where('description', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        return DataTables::of($bulkAttendances)
            ->addColumn('description', fn($bulk) => e($bulk->description))

            ->addColumn('students', function ($bulk) {
                return $bulk->students->pluck('fname')->implode(', ') ?: 'None';
            })

            ->addColumn('actions', function ($bulk) {
                $view = '';

                if (auth()->user()->hasAnyRole(['superAdmin', 'admin', 'financeAdmin'])) {
                    $view = '<a class="dropdown-item nav-main-link btn" href="' . url('/view-bulk-attendance', $bulk->id) . '">
                                <i class="fa fa-eye me-3"></i> View
                            </a>';
                }

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $view . '
                        </div>
                    </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorebulkAttendanceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorebulkAttendanceRequest $request)
    {
        $validated = $request->all();

        $studentsData = $validated['students'];
        $description = $validated['bulkAttendanceDescription'] ?? null;

        if (empty($studentsData)) {
            return response()->json([
                'message' => 'Student list must not be empty.'
            ], 422);
        }

        try {
            \DB::beginTransaction();

            $bulk = new BulkAttendance();
            $bulk->description = $description;
            $bulk->administrator_id = Auth::user()->administrator_id;
            $bulk->save();

            foreach ($studentsData as $attData) {
                Attendance::create([
                    'student_id'         => $attData['studentId'],
                    'bulk_attendance_id' => $bulk->id,
                    'administrator_id'   => $bulk->administrator_id,
                    'attendance_date'    => $attData['lessonDate'] ?? now(),
                    'instructor_id'      => $attData['instructorId'] ?? null,
                    'lesson_id'          => $attData['lessonId'] ?? null,
                ]);
            }

            \DB::commit();

            return response()->json([
                'message' => 'Bulk attendance saved successfully.'
            ], 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Bulk Attendance save error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'data' => $studentsData,
            ]);

            return response()->json([
                'message' => 'Failed to save bulk attendance. Please try again later.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\bulkAttendance  $bulkAttendance
     * @return \Illuminate\Http\Response
     */
    public function show(bulkAttendance $bulkAttendance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\bulkAttendance  $bulkAttendance
     * @return \Illuminate\Http\Response
     */
    public function edit(bulkAttendance $bulkAttendance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatebulkAttendanceRequest  $request
     * @param  \App\Models\bulkAttendance  $bulkAttendance
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatebulkAttendanceRequest $request, bulkAttendance $bulkAttendance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\bulkAttendance  $bulkAttendance
     * @return \Illuminate\Http\Response
     */
    public function destroy(bulkAttendance $bulkAttendance)
    {
        //
    }
}
