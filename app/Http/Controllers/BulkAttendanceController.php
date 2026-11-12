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
    public function __construct()
    {
        $this->middleware('permission:view students');
        $this->middleware('permission:view bulk attendances')->only('index');
        $this->middleware('permission:create bulk attendances')->only(['create', 'store']);
        $this->middleware('permission:edit bulk attendances')->only(['edit', 'update']);
        $this->middleware('permission:delete bulk attendances')->only('destroy');
        $this->middleware('permission:access operations')->only('show');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search.value');

        $restricted = !Auth::user()->can('view all students');
        $studentIds = $restricted ? Student::readableBy(Auth::user())->select('students.id') : null;
        $visibleStudents = function ($query) use ($studentIds) {
            $query->whereNull('attendances.deleted_at');
            if ($studentIds) {
                $query->whereIn('students.id', clone $studentIds);
            }
        };
        $bulkAttendances = BulkAttendance::with(['administrator', 'students' => $visibleStudents])
            ->when($restricted, function ($query) use ($visibleStudents, $studentIds) {
                $query->whereHas('students', $visibleStudents)
                    ->withCount(['attendances as inaccessible_attendances_count' => function ($attendance) use ($studentIds) {
                        $attendance->withTrashed()->where(function ($query) use ($studentIds) {
                            $query->whereNull('student_id')->orWhereNotIn('student_id', clone $studentIds);
                        });
                    }]);
            })
            ->when($search, function ($query) use ($search, $visibleStudents) {
                $query->where(function ($query) use ($search, $visibleStudents) {
                    $query->where('description', 'like', "%$search%")
                        ->orWhereHas('students', function ($students) use ($search, $visibleStudents) {
                            $visibleStudents($students);
                            $students->where(function ($students) use ($search) {
                                $students->where('fname', 'like', "%$search%")
                                    ->orWhere('mname', 'like', "%$search%")
                                    ->orWhere('sname', 'like', "%$search%");
                            });
                        });
                });
            })->orderByDesc('created_at');

        return DataTables::of($bulkAttendances)
            ->filter(function () {
                // Search already applied above
            })
            ->addColumn('description', fn($bulk) => e($bulk->description))

            ->addColumn('students', function ($bulk) {
                return $bulk->students
                    ->map(fn($student) => implode(' ', array_filter([$student->fname, $student->mname, $student->sname])))
                    ->implode(', ') ?: 'None';
            })

            ->addColumn('entered_by', fn($bulk) => $bulk->administrator
                ? trim("{$bulk->administrator->fname} {$bulk->administrator->sname}") : '-')

            ->addColumn('created_at', fn($bulk) => $bulk->created_at->format('d F, Y H:i:s'))

            ->addColumn('updated_at', fn($bulk) => $bulk->updated_at->format('d F, Y H:i:s'))

            ->addColumn('actions', function ($bulk) {
                $actions = '';

                if (!$bulk->inaccessible_attendances_count && auth()->user()->can('edit bulk attendances')) {
                    $actions .= '<a class="dropdown-item nav-main-link btn" href="' . route('bulk-attendances.edit', $bulk->id) . '">
                                <i class="fa fa-edit me-2"></i>Edit
                            </a>';
                }
                if (!$bulk->inaccessible_attendances_count && auth()->user()->can('delete bulk attendances')) {
                    $argument = e(json_encode(['id' => $bulk->id]));
                    $actions .= '<a class="dropdown-item nav-main-link btn text-danger" href="javascript:void(0);" onclick="openDeleteBulkAttendance(' . $argument . ')">
                                <i class="fa fa-trash me-2"></i>Delete
                            </a>';
                }

                if ($actions === '') {
                    return '';
                }

                return <<<HTML
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            {$actions}
                        </div>
                    </div>
                HTML;
    })

    ->only(['id', 'description', 'students', 'entered_by', 'created_at', 'updated_at', 'actions'])
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
        return view('attendances.addBulkAttendance');
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
        $this->ensureStudentsAccessible($studentsData);

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
    public function edit($id)
    {
        $bulkAttendance = BulkAttendance::with(['students' => fn ($query) => $query->whereNull('attendances.deleted_at')])->findOrFail($id);
        $this->ensureGroupAccessible($bulkAttendance);
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
            'students.*.lessonId' => 'required|exists:lessons,id',
            'bulkAttendanceDescription' => 'nullable|string|max:255',
        ]);

        $id = $request->route('bulk_attendance') ?? $request->input('students')[0]['bulkAttendanceId'] ?? $request->input('bulkAttendanceId');
        $this->ensureStudentsAccessible($request->students);
        DB::transaction(function () use ($id, $request) {
            $bulkAttendance = BulkAttendance::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->ensureGroupAccessible($bulkAttendance);
            $bulkAttendance->description = $request->bulkAttendanceDescription;
            $bulkAttendance->save();

            $existing = $bulkAttendance->attendances()->lockForUpdate()->get()
                ->groupBy(fn ($attendance) => json_encode([$attendance->student_id, (string) $attendance->lesson_id]));
            foreach ($request->students as $student) {
                $key = json_encode([$student['studentId'], (string) $student['lessonId']]);
                $matching = $existing->get($key);
                if ($matching && $matching->isNotEmpty()) {
                    $matching->shift();
                } else {
                    Attendance::create([
                        'student_id' => $student['studentId'],
                        'bulk_attendance_id' => $bulkAttendance->id,
                        'administrator_id' => $bulkAttendance->administrator_id,
                        'attendance_date' => now(),
                        'lesson_id' => $student['lessonId'],
                    ]);
                }
            }
            foreach ($existing as $remaining) {
                foreach ($remaining as $attendance) {
                    $attendance->delete();
                }
            }
        });

        return response()->json(['message' => 'Bulk attendance updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\bulkAttendance  $bulkAttendance
     * @return \Illuminate\Http\Response
     */
    public function destroy(BulkAttendance $bulkAttendance)
    {
        DB::transaction(function () use ($bulkAttendance) {
            $bulkAttendance = BulkAttendance::whereKey($bulkAttendance->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureGroupAccessible($bulkAttendance);
            // Detach all related students from pivot
            $bulkAttendance->students()->detach();

            Attendance::where('bulk_attendance_id', $bulkAttendance->id)->delete();

            // Delete the bulk attendance record
            $bulkAttendance->delete();

        });

        return response()->json(['message' => 'Bulk attendance deleted successfully.'], 200);
    }

    private function ensureStudentsAccessible(array $students): void
    {
        $ids = collect($students)->pluck('studentId')->unique()->values();
        abort_unless(Student::readableBy(Auth::user())->whereKey($ids->all())->count() === $ids->count(), 404);
    }

    private function ensureGroupAccessible(BulkAttendance $bulkAttendance): void
    {
        if (Auth::user()->can('view all students')) {
            return;
        }

        $studentIds = Student::readableBy(Auth::user())->select('students.id');
        $attendances = $bulkAttendance->attendances();
        abort_unless((clone $attendances)->whereIn('student_id', clone $studentIds)->exists(), 404);
        abort_if((clone $attendances)->withTrashed()->where(function ($query) use ($studentIds) {
            $query->whereNull('student_id')->orWhereNotIn('student_id', clone $studentIds);
        })->exists(), 404);
    }
}
