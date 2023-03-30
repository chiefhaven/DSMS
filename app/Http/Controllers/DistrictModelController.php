<?php

namespace App\Http\Controllers;

use App\Models\DistrictModel;
use App\Http\Requests\StoreDistrictModelRequest;
use App\Http\Requests\UpdateDistrictModelRequest;

class DistrictModelController extends Controller
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
     * @param  \App\Http\Requests\StoreDistrictModelRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDistrictModelRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DistrictModel  $districtModel
     * @return \Illuminate\Http\Response
     */
    public function show(DistrictModel $districtModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DistrictModel  $districtModel
     * @return \Illuminate\Http\Response
     */
    public function edit(DistrictModel $districtModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDistrictModelRequest  $request
     * @param  \App\Models\DistrictModel  $districtModel
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDistrictModelRequest $request, DistrictModel $districtModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DistrictModel  $districtModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(DistrictModel $districtModel)
    {
        //
    }
}
