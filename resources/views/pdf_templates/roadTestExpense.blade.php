<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

@include('pdf_templates.partials.header')
@include('pdf_templates.partials.watermark')
@include('pdf_templates.partials.pdf_template_style')
@include('pdf_templates.partials.groupHeader')
@include('pdf_templates.partials.letter_head')


    <h3 style="text-transform: uppercase;"><u>APPLICATION FOR STUDENTS' ROAD TEST</u></h3>

    <P>I write to seek your assistance for the following students to take their road test.</P><p></p>
    <div class="bg-body" style="z-index:999 !important">
        <table class="table table-striped table-responsive" style="font-size:12px;">
            <thead style="color: #ffffff !important; background-color:#0665d0; text-align:left !important">
                <th class="invoice-td" style="text-align:left !important">Student</th>
                <th class="invoice-td">Class</th>
                <th class="invoice-td">Veihcle Reg Number</th>
            </thead>
            <tbody>
                @foreach ($expense->students as $student)
                    <tr class="py-1" style="padding-top: 0px; padding-bottom: 0px; ">
                        <td class="invoice-td text-uppercase">
                            {{$student->fname}} {{$student->mname}} <b>{{$student->sname}}</b>
                        </td>
                        <td class="invoice-td text-center">
                            {{$student->course->class}}
                        </td>
                        <td class="invoice-td text-center">
                            {{$student->fleet->car_registration_number}}
                        </td>
                    </tr>
                    @endforeach
            </tbody>
        </table>
    </div>
</div>

@include('pdf_templates.partials.groupFooter')
