<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\InvoiceSetting;
use Illuminate\Http\Request;
use App\Models\District;
use RealRashid\SweetAlert\Facades\Alert;


class SettingController extends Controller
{
    protected $setting;

    public function __construct()
    {
        $this->middleware(['role:superAdmin|admin']);
        $this->setting = Setting::find(1);
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
        $setting = $this->setting;
        $invoice_setting = InvoiceSetting::find(1);
        $district = district::get();
        return view('settings.settings', compact('setting', 'district', 'invoice_setting'));
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

        $settings = $this->setting;
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
        if($settings->save()){
            Alert::toast('School settings updated successifully', 'success');
        }

        return redirect('/settings')->with('message', 'Settings updated!');
    }

    public function attendanceTimeUpdate(Request $request)
    {
        $messages = [
            'timestart.required' => 'The "Start time" is required.',
            'timestart.date_format' => 'The "Start time" must be of fomart Hours and Minutes.',
            'timestop.required'   => 'The "Stop time" is required.',
            'timestop.date_format'   => 'The "Stop time" must be of fomart Hours and Minutes.',
            'time_between_attendances.required'   => 'The "Time between attendances" is required.',
            'time_between_attendances.between'   => 'The "Time between attendances" must be between 0 and 59 (in minutes).',
            'time_between_attendances.integer'   => 'The "Time between attendances" must be a number.',
            'lesson_threshold'   => 'Fees threshold must be a number from 0 to 100',
            'fees_threshold'   => 'Lesson threshold must be a number from 0 to 100',
            'fees_code_i_threshold'   => 'Fees highway code I threshold must be a number from 0 to 100',
            'fees_code_ii_threshold'   => 'Fees highway code II must be a number from 0 to 100',
            'fees_road_test_threshold'   => 'Fees road test must be a number from 0 to 100',
        ];

        //Validate the request
        $this->validate($request, [
            'timestart'  =>'required|date_format:H:i',
            'timestop' =>'required|date_format:H:i',
            'time_between_attendances' => 'required|integer|between:0,59',
            'fees_threshold' =>'required|integer|between:0,100',
            'lesson_threshold' =>'required|integer|between:0,100',
            'fees_road_test_threshold' => 'required|integer|between:0,100',
            'fees_code_i_threshold'=> 'required|integer|between:0,100',
            'fees_code_ii_threshold' => 'required|integer|between:0,100',

        ], $messages);


        $post = $request->All();

        $settings = $this->setting;

        $settings->attendance_time_start = $post['timestart'];
        $settings->attendance_time_stop = $post['timestop'];
        $settings->time_between_attendances = $post['time_between_attendances'];
        $settings->fees_balance_threshold = $post['fees_threshold'];
        $settings->attendance_threshold = $post['lesson_threshold'];
        $settings->fees_code_i_threshold =$post['fees_code_i_threshold'];
        $settings->fees_code_ii_threshold =$post['fees_code_ii_threshold'];
        $settings->fees_road_threshold = $post['fees_road_test_threshold'];

        $settings->save();
        if($settings->save()){
            Alert::toast('System settings updated successifully', 'success');
            return redirect('/settings');
        }

        Alert()->error('System settings update not successfull');
        return back();


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
