<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

    @include('pdf_templates.partials.header')
    @include('pdf_templates.partials.qrcode')
    @include('pdf_templates.partials.watermark')

<div class="container" style="padding: 60px">
    <div class="row">
        <div class="container">
            <div class="col-lg-12">
                <h1 style="text-transform: uppercase;"><img src="{{ public_path("media/{$setting->logo}") }}" alt="Sign here" style="width: 100%; height: auto;"></h1>
                <h3 style="text-align:center">ATTENDANCE SUMMARY</h3>
            </div>
            <div class="row">
                <div class="col-lg-12">
                <table class="table">
                    <thead>
                        <th style="width:70%;"></th>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: solid #ffffff00; text-align:left; padding-left: 0px !important;">
                                {{$instructor->instructor->fname}} {{$instructor->instructor->sname}}<br>
                                {{$instructor->instructor->fleet->car_registration_number}}
                                {{$instructor->instructor->fleet->car_brand_model}}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>

            <table class="table table-striped table-responsive" style="font-size:12px; background-color: #ffffff">
                    <thead style="color: #ffffff !important; background-color:#0665d0;">
                        <th class="invoice-td" style="text-align:left">Date</th>
                        <th class="invoice-td" style="text-align:left">Student</th>
                        <th class="invoice-td" style="text-align:left">Lesson Attended</th>
                        <th class="invoice-td" style="text-align:left">Student Signature</th>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            <tr class="py-1" style="padding-top: 0px; padding-bottom: 0px; ">
                                <td class="invoice-td">
                                    <b>{{ $attendance->attendance_date->format('j F, Y') }}</b>
                                    {{ $attendance->attendance_date->format('H:i:s') }}
                                </td>
                                <td class="invoice-td">
                                    @if($attendance->student)
                                        {{$attendance->student->fname}} {{$attendance->student->mname}} {{$attendance->student->sname}}
                                    @endif
                                </td>
                                <td class="invoice-td">
                                    {{$attendance->lesson->name}}
                                </td>
                                <td class="invoice-td">
                                    <!-- <img src="{{ public_path("media/signatures/{$attendance->student->signature}") }}" alt="" style="width: auto; height: 20px;"></p> -->
                                </td>
                            </tr>
                            @endforeach
                    </tbody>
            </table>
        </div>
    </div>
</div>
</div>

</body>
</html>
