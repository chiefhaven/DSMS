<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Fleet;
use App\Models\Student;
use Illuminate\Http\Request;

class studentController extends Controller
{
    public function showClassRoom($classroom)
    {
        $user = auth()->user();
        if (!$user->can('view classrooms')) {
            $assignedStudent = $user->student_id && Student::whereKey($user->student_id)->where('classroom_id', $classroom)->exists();
            $assignedInstructor = $user->instructor_id && Classroom::whereKey($classroom)
                ->whereHas('instructors', fn ($query) => $query->whereKey($user->instructor_id))->exists();
            abort_unless($assignedStudent || $assignedInstructor, 403);
        }
        $classRoom = Classroom::with('instructors')->find($classroom);

        if (!$classRoom) {
            return response()->json(['error' => 'Classroom not found'], 404);
        }

        return response()->json($classRoom);
    }

    public function showFleet($fleet)
    {
        $user = auth()->user();
        if (!$user->can('view fleet')) {
            $assignedStudent = $user->student_id && Student::whereKey($user->student_id)->where('fleet_id', $fleet)->exists();
            $assignedInstructor = $user->instructor_id && Fleet::whereKey($fleet)->where('instructor_id', $user->instructor_id)->exists();
            abort_unless($assignedStudent || $assignedInstructor, 403);
        }
        $fleet = Fleet::with('instructor')->find($fleet);

        if (!$fleet) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        return response()->json($fleet);
    }
}
