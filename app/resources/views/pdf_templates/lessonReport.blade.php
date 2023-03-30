<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="{{ public_path('css/bootstrap.css') }}">
    <style>
        @page {
            margin: 25px;
        }

        body {
            color: #001028;
            background: #FFFFFF;
            font-family : DejaVu Sans, Helvetica, sans-serif;
            font-size: 14px;
            margin-bottom: 50px;
        }

        a {
            color: #5D6975;
            text-decoration: none;
        }

        h1 {
            color: #5D6975;
            font-size: 2.8em;
            line-height: 1.4em;
            font-weight: bold;
            margin: 0;
        }

        table {
            width: 100%;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        th, .section-header {
            padding: 5px 10px;
            white-space: nowrap;
            font-weight: normal;
        }

        tbody {
            padding: 5px 10px;
            color: #5D6975;
            white-space: nowrap;
            font-weight: normal;
        }

        td {
            padding: 10px;
        }

        table.alternate tr:nth-child(odd) td {
            background: #ffffff;
        }

        th.amount, td.amount {
            text-align: right;
        }

        .info {
            color: #5D6975;
            font-weight: bold;
        }

        .terms {
            padding: 10px;
        }

        .footer {
            position: fixed;
            height: 50px;
            width: 100%;
            bottom: 0;
            text-align: center;
        }

       .table-striped tbody tr:nth-of-type(odd) {

            background-color: rgba(0, 0, 0, 0.05);

        }
    
    

        .table-bordered td {
          border: 1px solid #dee2e6;
        }
            
        .invoice{   
                position: absolute;
                z-index: 999;
                margin: 4em !important;
        }

        .items table{
            background: #ffffff;
        }


        #watermark {
            height: 100%;
            width: 100%;
            max-width: 100%;
            position: absolute;
            overflow: hidden;
            opacity: 0.1;
            z-index: 0;
        }

        #watermark img {
          max-width: 100%;
        }

        #watermark p {
            position: relative;
            top: -300px;
            left: -300px;
            height: 200%;
            width: 200%;
            font-size: 30px;
            pointer-events: none;
            -webkit-transform: rotate(23deg);
            line-height: 1;
            color: #999999;
        }

    </style>
</head>
<body>

<div style="height: 30.5cm; width: 22cm; overflow: hidden; position: absolute; top: 0; left: 0;">
    <div id="watermark">
        <p>
            @for($i = 0; $i < 1000; $i++)

                {{$student->fname}} {{$student->sname}}

            @endfor
        </p>
    </div>
</div>

<div class="container invoice">
    <div class="row">
    <div class="col-8">
    <h1>DARON DRIVING SCHOOL</h1>
    <h3>Lesson Attendance Report</h3>
    <div class="row">
        <div class="col-12">
        <table class="table">
            <thead>
                <th style="width:70%;"></th>
                <th></th>
            </thead>
            <tbody>
                <tr>
                    <td style="border: solid #ffffff00; text-align:left; padding-left: 0px !important;">
                        Name of Student: {{$student->fname}} {{$student->sname}}<br>
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
                <th>Date</th>
                <th>Lesson Attended</th>
                <th>Name of Instructor</th>
                <th>Instructor Signature</th>
                <th>Student Signature</th>
            </thead>
            <tbody>
                @foreach ($attendance as $attendance)
                    <tr>
                        <td>
                            {{$attendance->attendance_date->format('j F, Y')}}
                        </td>
                        <td>
                            {{$attendance->lesson->name}}
                        </td>
                        <td>
                            {{$attendance->instructor->fname}} {{$attendance->instructor->sname}}
                        </td>
                        <td>
                            <img src="{{ public_path("media/signatures/{$setting->authorization_signature}") }}" alt="" style="width: auto; height: 20px;"></p>
                        </td>
                        <td>
                            <img src="{{ public_path("media/signatures/{$student->signature}") }}" alt="" style="width: auto; height: 20px;"></p>
                        </td>
                    </tr>
                    @endforeach
            </tbody>
    </table>

</div>
</div>
</div>

</body>
</html>