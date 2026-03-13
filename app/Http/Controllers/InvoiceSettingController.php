<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storeinvoice_settingRequest;
use App\Models\InvoiceSetting;
use App\Http\Requests\StoreInvoiceSettingRequest;
use App\Http\Requests\Updateinvoice_settingRequest;
use App\Http\Requests\UpdateInvoiceSettingRequest;
use RealRashid\SweetAlert\Facades\Alert;

class InvoiceSettingController extends Controller
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
     * @param  \App\Http\Requests\StoreInvoiceSettingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Storeinvoice_settingRequest $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InvoiceSetting  $InvoiceSetting
     * @return \Illuminate\Http\Response
     */
    public function show(InvoiceSetting $InvoiceSetting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\InvoiceSetting  $InvoiceSetting
     * @return \Illuminate\Http\Response
     */
    public function edit(InvoiceSetting $InvoiceSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInvoiceSettingRequest  $request
     * @param  \App\Models\InvoiceSetting  $InvoiceSetting
     * @return \Illuminate\Http\Response
     */
    public function update(Updateinvoice_settingRequest $request, InvoiceSetting $InvoiceSetting)
    {

        $post = $request->All();

        $settings = InvoiceSetting::find(1);

        $settings->invoice_due_days = $post['due'];
        $settings->year = $post['year'];
        $settings->prefix = $post['prefix'];
        $settings->terms = $post['terms'];
        $settings->footer = $post['footer'];
        $settings->header = $post['header'];
        $settings->invoice_logo = $post['invoice_logo'];

        $settings->save();

        Alert::toast('Invoice settings updated successifully', 'success');

        return response()->json(['success'=>'Data is successfully added']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InvoiceSetting  $InvoiceSetting
     * @return \Illuminate\Http\Response
     */
    public function destroy(InvoiceSetting $InvoiceSetting)
    {
        //
    }
}
