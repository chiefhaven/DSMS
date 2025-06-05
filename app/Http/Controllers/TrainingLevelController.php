<?php

namespace App\Http\Controllers;

use App\Models\TrainingLevel;
use App\Http\Requests\StoreTrainingLevelRequest;
use App\Http\Requests\UpdateTrainingLevelRequest;

class TrainingLevelController extends Controller
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
     * @param  \App\Http\Requests\StoreTrainingLevelRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTrainingLevelRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TrainingLevel  $trainingLevel
     * @return \Illuminate\Http\Response
     */
    public function show(TrainingLevel $trainingLevel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TrainingLevel  $trainingLevel
     * @return \Illuminate\Http\Response
     */
    public function edit(TrainingLevel $trainingLevel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTrainingLevelRequest  $request
     * @param  \App\Models\TrainingLevel  $trainingLevel
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTrainingLevelRequest $request, TrainingLevel $trainingLevel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TrainingLevel  $trainingLevel
     * @return \Illuminate\Http\Response
     */
    public function destroy(TrainingLevel $trainingLevel)
    {
        //
    }
}
