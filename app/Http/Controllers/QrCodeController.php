<?php

namespace App\Http\Controllers;

use App\Models\qrCode;
use App\Http\Requests\StoreqrCodeRequest;
use App\Http\Requests\UpdateqrCodeRequest;
use Auth;

class QrCodeController extends Controller
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
     * @param  \App\Http\Requests\StoreqrCodeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreqrCodeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\qrCode  $qrCode
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(Auth::user()){

        }

        else{
            return view('qrCodeGuest');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\qrCode  $qrCode
     * @return \Illuminate\Http\Response
     */
    public function edit(qrCode $qrCode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateqrCodeRequest  $request
     * @param  \App\Models\qrCode  $qrCode
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateqrCodeRequest $request, qrCode $qrCode)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\qrCode  $qrCode
     * @return \Illuminate\Http\Response
     */
    public function destroy(qrCode $qrCode)
    {
        //
    }
}
