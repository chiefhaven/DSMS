<?php

namespace App\Http\Controllers;

use App\Models\attendanceSchedule;
use App\Http\Requests\StoreattendanceScheduleRequest;
use App\Http\Requests\UpdateattendanceScheduleRequest;

class AttendanceScheduleController extends Controller
{
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreattendanceScheduleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreattendanceScheduleRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\attendanceSchedule  $attendanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function show(attendanceSchedule $attendanceSchedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\attendanceSchedule  $attendanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function edit(attendanceSchedule $attendanceSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateattendanceScheduleRequest  $request
     * @param  \App\Models\attendanceSchedule  $attendanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateattendanceScheduleRequest $request, attendanceSchedule $attendanceSchedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\attendanceSchedule  $attendanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function destroy(attendanceSchedule $attendanceSchedule)
    {
        //
    }
}
