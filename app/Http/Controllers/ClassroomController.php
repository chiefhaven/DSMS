<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Instructor;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Fleet;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function __construct()
    {

        $this->middleware('permission:view classrooms')->only(['index', 'show']);
        $this->middleware('permission:view classrooms|assign students')->only('getClassrooms');
        $this->middleware('permission:create classrooms')->only(['create', 'store']);
        $this->middleware('permission:edit classrooms')->only(['edit', 'update']);
        $this->middleware('permission:delete classrooms')->only('destroy');

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $classrooms = Classroom::get();
        return view('classrooms.classrooms', compact('classrooms'));
    }

    public function getClassrooms()
    {
        $classrooms = Classroom::with('instructors')->get();

        return response()->json($classrooms, 200);
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
     * @param  \App\Http\Requests\StoreClassroomRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreClassroomRequest $request)
    {
        // Retrieve validated data
        $data = $request->validated();

        // Create the Classroom
        $classroom = Classroom::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
        ]);

        // Assign the instructor to the classroom
        $classroom->instructors()->attach($data['instructor']);
        $classroom->save();

        // Return success response
        return response()->json([
            'message' => 'Classroom created successfully!',
            'classroom' => $classroom,
        ], 201);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Classroom  $classroom
     * @return \Illuminate\Http\Response
     */
    public function show(Classroom $classroom)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Classroom  $classroom
     * @return \Illuminate\Http\Response
     */
    public function edit(Classroom $classroom)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateClassroomRequest  $request
     * @param  \App\Models\Classroom  $classroom
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateClassroomRequest $request, ?Classroom $classroom = null)
    {
        // Retrieve validated data
        $data = $request->validated();

        $classroom = $this->resolveClassroom($request, $classroom);
        $fleet = Fleet::where('instructor_id', $data['instructor'])->first();
        if ($fleet) {
            abort_unless(auth()->user()->can('edit fleet'), 403);
        }

        // Update the existing classroom
        $classroom->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
        ]);

        // Find the fleet assigned to the instructor
        if ($fleet) {
            // Unassign the instructor if the fleet is found
            $fleet->instructor_id = null;
            $fleet->save();

            return response()->json([
                'message' => 'Instructor has been unassigned from the fleet.',
            ]);
        }

        // Check if 'instructor' is provided and update the relationship
        if (isset($data['instructor'])) {
            $classroom->instructors()->sync($data['instructor']);
        }

        // Return success response
        return response()->json([
            'message' => 'Classroom updated successfully!',
            'classroom' => $classroom->load('instructors'), // Load related instructors
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Classroom  $classroom
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, ?Classroom $classroom = null)
    {
        $classroom = $this->resolveClassroom($request, $classroom);
        try {

            // Optionally check for related data and handle deletion logic
            if ($classroom->students()->exists()) {
                return response()->json([
                    'message' => 'Classroom has associated students and cannot be deleted.'
                ], 400);
            }

            // Delete the classroom
            $classroom->delete();

            return response()->json([
                'message' => 'Classroom deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'message' => 'An error occurred while deleting the classroom.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function resolveClassroom(Request $request, ?Classroom $classroom): Classroom
    {
        if ($classroom && $classroom->exists) {
            return $classroom;
        }

        return Classroom::findOrFail($request->route('classroom') ?? $request->route('id') ?? $request->input('classroom_id'));
    }
}
