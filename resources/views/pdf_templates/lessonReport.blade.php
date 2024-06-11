<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

@include('pdf_templates.partials.header')
@include('pdf_templates.partials.watermark')

<div class="container" style="padding-left:20px">
    <div class="row">
        <div class="container">
            <div class="col-lg-12">
                <h1 style="text-transform: uppercase;"><img src="{{ public_path("media/{$setting->logo}") }}" alt="Sign here" style="width: 100%; height: auto;"></h1>
                <h3 style="text-align:center">LESSONS ATTENDANCE REPORT</h3>
            </div>
            <div class="row">
                <div class="col-lg-12">
                <table class="table">
                    <thead>
                        <th style="width:70%;"></th>
                        <th></th>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: solid #ffffff00; text-align:left; padding-left: 0px !important;">
                                Name of Student: {{$student->fname}} {{$student->mname}} {{$student->sname}}<br>
                                Address: {{$student->address}}<br>
                            </td>
                            <td style="border: solid #ffffff00;" valign="top">Phone: {{$student->phone}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>

            <table class="table table-striped table-responsive" style="font-size:12px; background-color: #ffffff">
                    <thead style="color: #ffffff !important; background-color:#0665d0;">
                        <th class="invoice-td">Date</th>
                        <th class="invoice-td">Lesson Attended</th>
                        <th class="invoice-td">Name of Instructor</th>
                        <th class="invoice-td">Instructor Signature</th>
                        <th class="invoice-td">Student Signature</th>
                    </thead>
                    <tbody>
                        @foreach ($attendance as $attendance)
                            <tr class="py-1" style="padding-top: 0px; padding-bottom: 0px; ">
                                <td class="invoice-td">
                                    {{$attendance->attendance_date->format('j F, Y')}}
                                </td>
                                <td class="invoice-td">
                                    {{$attendance->lesson->name}}
                                </td>
                                <td class="invoice-td">
                                    {{$attendance->instructor->fname}} {{$attendance->instructor->sname}}
                                </td>
                                <td class="invoice-td">
                                    <!-- <img src="{{ public_path("media/signatures/{$setting->authorization_signature}") }}" alt="" style="width: auto; height: 20px;"></p>-->
                                </td>
                                <td class="invoice-td">
                                    <!-- <img src="{{ public_path("media/signatures/{$student->signature}") }}" alt="" style="width: auto; height: 20px;"></p> -->
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
