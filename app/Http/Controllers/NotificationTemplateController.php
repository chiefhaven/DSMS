<?php

namespace App\Http\Controllers;

use App\Models\notification_template;
use App\Http\Requests\Storenotification_templateRequest;
use App\Http\Requests\Updatenotification_templateRequest;

class NotificationTemplateController extends Controller
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
        $templates = notification_template::get();
        return view('sms_templates', compact('templates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Storenotification_templateRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(notification_template $notification_template)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(notification_template $notification_template)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Updatenotification_templateRequest $request, notification_template $notification_template)
    {
        $messages = [
            'body.required' => '"Body" field is required!',
        ];

        // Validate the request
        $this->validate($request, [
            'body'  =>'required'

        ], $messages);

        $post = $request->All();

        $template = notification_template::where('type', $post['type'])->firstOrFail();

        $template->body = $post['body'];

        $template->save();

        return redirect()->back();


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(notification_template $notification_template)
    {
        //
    }
}
