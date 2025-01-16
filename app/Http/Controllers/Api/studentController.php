<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;

class studentController extends Controller
{
    public function __construct()
    {

        $this->middleware(['role:student']);

    }

    public function showClassRoom($classroom)
    {
        $classRoom = Classroom::find($classroom);

        if (!$classRoom) {
            return response()->json(['error' => 'Classroom not found'], 404);
        }

        return response()->json($classRoom);
    }
}
