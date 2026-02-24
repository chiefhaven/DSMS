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
@include('pdf_templates.partials.groupHeader')

@include('pdf_templates.partials.stamp')
@include('pdf_templates.partials.seal')


    <h3 style="text-transform: uppercase;"><u>APPLICATION FOR STUDENT’S APTITUDE TEST</u></h3>

    <P>I write to seek your assistance for the following students to take their aptitude test.</P>
    <table class="table table-striped table-responsive" style="font-size:12px; background-color: #ffffff !important; overflow:visible">
        <thead style="color: #ffffff !important; background-color:#0665d0; text-align:left !important;">
            <th class="invoice-td" style="text-align:left !important">Student name</th>
            <th class="invoice-td">TRN</th>
            <th class="invoice-td">Code</th>
            <th class="invoice-td">Highway code I</th>
            <th class="invoice-td">Highway code II</th>
        </thead>
        <tbody>
            @foreach ($expense->students as $student)
                <tr class="py-1" style="padding-top: 0px; padding-bottom: 0px; ">
                    <td class="invoice-td text-uppercase">
                        {{$student->fname}} {{$student->mname}} {{$student->sname}}
                    </td>
                    <td class="invoice-td text-center">
                        {{$student->trn}}
                    </td>
                    <td class="invoice-td text-center">
                            @if (!$student->course)
                                -
                            @else
                                {{$student->course->class}}
                            @endif
                    </td>
                    <td class="invoice-td text-center">
                        @if ($student->pivot->expense_type == '9fd3074c-3d1b-4a58-9ea5-e7e6336296e9')
                            <div style="font-family: DejaVu Sans, sans-serif;">✔</div>
                        @endif
                    </td>
                    <td class="invoice-td text-center">
                        @if ($student->pivot->expense_type == '9fd3074c-42a4-4aef-8818-e5379f1fa0cc')
                            <div style="font-family: DejaVu Sans, sans-serif;">✔</div>
                        @endif
                    </td>
                </tr>
                @endforeach
        </tbody>
    </table>
</div>

@include('pdf_templates.partials.groupFooter')
