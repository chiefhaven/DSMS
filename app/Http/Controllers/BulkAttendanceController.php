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
            $bulkAttendances = BulkAttendance::with(['administrator', 'students'])
            ->when($search, function ($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%$search%")
                    ->orWhereHas('students', fn($s) =>
                        $s->where('fname', 'like', "%$search%")
                            ->orWhere('mname', 'like', "%$search%")
                            ->orWhere('sname', 'like', "%$search%")
                    );
                });
            })
            ->orderByDesc('created_at');
        }

        return DataTables::of($bulkAttendances)
            ->addColumn('description', fn($bulk) => e($bulk->description))

            ->addColumn('students', function ($bulk) {
                return $bulk->students
                    ->map(fn($student) => trim("{$student->fname} {$student->mname} {$student->sname}"))
                    ->implode(', ') ?: 'None';
            })

            ->addColumn('entered_by', fn($bulk) => "{$bulk->administrator->fname} {$bulk->administrator->sname}")

            ->addColumn('created_at', fn($bulk) => $bulk->created_at->format('d F, Y H:i:s'))

            ->addColumn('updated_at', fn($bulk) => $bulk->updated_at->format('d F, Y H:i:s'))

            ->addColumn('actions', function ($bulk) {
                $actions = '';

                if (auth()->user()->hasAnyRole(['superAdmin', 'admin', 'financeAdmin'])) {
                    $view = '<a class="dropdown-item nav-main-link btn" href="' . url("/view-bulk-attendance/{$bulk->id}") . '">
                                <i class="fa fa-eye me-2"></i>View
                            </a>';

                    $edit = '<a class="dropdown-item nav-main-link btn" href="' . url("/edit-bulk-attendance/{$bulk->id}") . '">
                                <i class="fa fa-edit me-2"></i>Edit
                            </a>';

                    $delete = '<a class="dropdown-item nav-main-link btn text-danger" href="javascript:void(0);" onclick="openDeletebulkAttendance(\'' . $bulk->id . '\')">
                                <i class="fa fa-trash me-2"></i>Delete
                            </a>';

                    $actions = $view . $edit . $delete;
                }

                return <<<HTML
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            {$actions}
                        </div>
                    </div>
                HTML;
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
    public function edit(bulkAttendance $bulkAttendance, $id)
    {
        $bulkAttendance = BulkAttendance::with('students')->findOrFail($id);
        return view('attendances.editBulkAttendance', compact('bulkAttendance'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatebulkAttendanceRequest  $request
     * @param  \App\Models\bulkAttendance  $bulkAttendance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'students' => 'required|array|min:1',
            'students.*.studentId' => 'required|uuid|exists:students,id',
            'students.*.lessonId' => 'required|integer|exists:lessons,id',
            'bulkAttendanceDescription' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // Find the bulkAttendance record
            $bulkAttendance = BulkAttendance::findOrFail($request->input('students')[0]['bulkAttendanceId'] ?? $request->input('bulkAttendanceId'));

            // Update description
            $bulkAttendance->description = $request->bulkAttendanceDescription;
            $bulkAttendance->save();

            // Remove old student pivot entries
            $bulkAttendance->students()->detach();

            // Re-attach students with lesson_id on pivot
            foreach ($request->students as $student) {
                $bulkAttendance->students()->attach($student['studentId'], [
                    'lesson_id' => $student['lessonId'],
                    //'lesson_quantity' => $student['lessonQuantity'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Bulk attendance updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update bulk attendance.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\bulkAttendance  $bulkAttendance
     * @return \Illuminate\Http\Response
     */
    public function destroy(BulkAttendance $bulkAttendance)
    {
        try {
            // Detach all related students from pivot
            $bulkAttendance->students()->detach();

            Attendance::where('bulk_attendance_id', $bulkAttendance->id)->delete();

            // Delete the bulk attendance record
            $bulkAttendance->delete();

            return response()->json([
                'message' => 'Bulk attendance deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete bulk attendance.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
