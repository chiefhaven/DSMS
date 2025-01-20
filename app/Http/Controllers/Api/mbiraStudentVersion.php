<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class MbiraStudentVersion extends Controller
{
    public function index()
    {
        $version = Setting::find(1)->mobile_version;

        return response()->json(['requiredVersion' => $version]);
    }
}
