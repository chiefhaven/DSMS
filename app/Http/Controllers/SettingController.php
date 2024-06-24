<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\InvoiceSetting;
use Illuminate\Http\Request;
use App\Models\District;
use RealRashid\SweetAlert\Facades\Alert;


class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:superAdmin'], ['role:admin']);
    }

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function edit(Setting $setting)
    {
        $setting = Setting::find(1);
        $invoice_setting = InvoiceSetting::find(1);
        $district = district::get();
        return view('settings', compact('setting', 'district', 'invoice_setting'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Setting $setting)
    {
        $messages = [
            'school_name.required' => 'The "School Name" field is required!',
            'company_description.required'   => 'The "Company Description" field is should be unique!',
            'email.required' => 'Email is required!',
            'address.required'   => 'Address is required',
            'slogan.required'   => 'Slogan is required',
            'District.required'   => 'District is required',
            'time_zone.required'   => 'Time Zone is required',
        ];

        //Validate the request
        $this->validate($request, [
            'school_name'  =>'required',
            'company_description' =>'required',
            'email'   =>'required',
            'address' =>'required',
            'postal'  =>'required',
            'slogan' =>'required',
            'district' =>'required',
            'phone_1' =>'required',
            'time_zone' =>'required'

        ], $messages);


        $post = $request->All();

        $settings = Setting::find(1);
        $district = havenUtils::selectDistrict($post['district']);

        //signature processing
        if($request->file('authorization_signature')){
            $signatureName = time().$request->file('authorization_signature')->getClientOriginalName();
            $request->authorization_signature->move(public_path('media/signatures'), $signatureName);
            $settings->authorization_signature = $signatureName;
        }

        //logo processing
        if($request->file('logo')){
            $logoName = time().$request->file('logo')->getClientOriginalName();
            $request->logo->move(public_path('media'), $logoName);
            $settings->logo = $logoName;
        }

        //favicon processing
        if($request->file('favicon')){
            $faviconName = 'favicon.png';
            $request->favicon->move(public_path('media/favicons'), $faviconName);
            $settings->favicon = $faviconName;
        }

        $settings->school_name = $post['school_name'];
        $settings->slogan = $post['slogan'];
        $settings->company_description = $post['company_description'];
        $settings->district_id = $district;
        $settings->postal = $post['postal'];
        $settings->time_zone = $post['time_zone'];
        $settings->email = $post['email'];
        $settings->phone_1 = $post['phone_1'];
        $settings->phone_2 = $post['phone_2'];
        $settings->address = $post['address'];

        $settings->save();
        Alert::toast('School settings updated successifully', 'success');

        return redirect('/settings')->with('message', 'Settings updated!');
    }

    public function attendanceTimeUpdate(Request $request)
    {
        dd($request['timestart']);

        $messages = [
            'timestart.required' => 'The "Start time" is required.',
            'timestart.date_format' => 'The "Start time" must be of fomart Hours and Minutes.',
            'timestop.required'   => 'The "Stop time" is required.',
            'timestop.date_format'   => 'The "Stop time" must be of fomart Hours and Minutes.',
            'time_between_attendances.required'   => 'The "Time between attendances" is required.',
            'time_between_attendances.between'   => 'The "Time between attendances" must be between 0 and 59 minutes.',
        ];

        //Validate the request
        $this->validate($request, [
            'timestart'  =>'required|date_format:H:i',
            'timestop' =>'required|date_format:H:i',
            'time_between_attendances' => 'required|integer|between:0,59',

        ], $messages);


        $post = $request->All();

        $settings = Setting::find(1);

        $settings->attendance_time_start = $post['timestart'];
        $settings->attendance_time_stop = $post['timestop'];

        $settings->save();
        Alert::toast('Attendance settings updated successifully', 'success');

        return redirect('/settings');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function destroy(Setting $setting)
    {
        //
    }
}
