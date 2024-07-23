<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

@include('pdf_templates.partials.header')
@include('pdf_templates.partials.watermark')
@include('pdf_templates.partials.pdf_template_style')
@include('pdf_templates.partials.letter_head')


    <h3 style="text-transform: uppercase;"><u>APPLICATION FOR A ROAD TEST</u></h3>

    <P>I write to seek your assistance for the following students to take their road test.</P><p> </p>
    <table class="table table-striped table-responsive" style="font-size:12px; background-color: #ffffff">
        <thead style="color: #ffffff !important; background-color:#0665d0; text-align:left !important">
            <th class="invoice-td" style="text-align:left !important">Student</th>
            <th class="invoice-td" style="text-align:left !important">Class</th>
            <th class="invoice-td" style="text-align:left !important">Veihcle Reg Number</th>
        </thead>
        <tbody>
            @foreach ($expense->student as $student)
                <tr class="py-1" style="padding-top: 0px; padding-bottom: 0px; ">
                    <td class="invoice-td">
                        {{$student->fname}} {{$student->mname}} {{$student->sname}}
                    </td>
                    <td class="invoice-td">
                        {{$student->course->class}}
                    </td>
                    <td class="invoice-td">
                        {{$student->fleet->car_registration_number}}
                    </td>
                </tr>
                @endforeach
        </tbody>
    </table>
</div>

@include('pdf_templates.partials.groupFooter')
