<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\havenUtils;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Account;
use App\Models\User;
use App\Models\District;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\Setting;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Session;
use Illuminate\Support\Str;
use PDF;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Auth;

class studentProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $id = Auth::user()->student_id;
        $student = Student::with('User', 'Invoice', 'Course', 'Attendance')->find($id);
        return response()->json($student);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function showAttendance()
    {
        $id = Auth::user()->student_id;
        $student = Attendance::with('Attendance')->find($id);
        return response()->json($student);
    }
}
