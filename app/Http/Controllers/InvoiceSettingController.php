<?php

namespace App\Http\Controllers;

use App\Models\invoice_setting;
use App\Http\Requests\Storeinvoice_settingRequest;
use App\Http\Requests\Updateinvoice_settingRequest;
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
     * @param  \App\Http\Requests\Storeinvoice_settingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Storeinvoice_settingRequest $request)
    {
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\invoice_setting  $invoice_setting
     * @return \Illuminate\Http\Response
     */
    public function show(invoice_setting $invoice_setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\invoice_setting  $invoice_setting
     * @return \Illuminate\Http\Response
     */
    public function edit(invoice_setting $invoice_setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Updateinvoice_settingRequest  $request
     * @param  \App\Models\invoice_setting  $invoice_setting
     * @return \Illuminate\Http\Response
     */
    public function update(Updateinvoice_settingRequest $request, invoice_setting $invoice_setting)
    {

        $post = $request->All();

        $settings = Invoice_Setting::find(1);
 
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
     * @param  \App\Models\invoice_setting  $invoice_setting
     * @return \Illuminate\Http\Response
     */
    public function destroy(invoice_setting $invoice_setting)
    {
        //
    }
}
