<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Fleet;
use Illuminate\Http\Request;

class studentController extends Controller
{
    public function showClassRoom($classroom)
    {
        $classRoom = Classroom::with('instructors')->find($classroom);

        if (!$classRoom) {
            return response()->json(['error' => 'Classroom not found'], 404);
        }

        return response()->json($classRoom);
    }

    public function showFleet($fleet)
    {
        $fleet = Fleet::with('instructor')->find($fleet);

        if (!$fleet) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        return response()->json($fleet);
    }
}
