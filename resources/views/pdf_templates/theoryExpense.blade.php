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


    <h3 style="text-transform: uppercase;"><u>APPLICATION FOR STUDENT’S APTITUDE TEST</u></h3>

    <P>I write to seek your assistance for the following students to take their aptitude test.</P>
    <table class="table table-striped table-responsive" style="font-size:12px; background-color: #ffffff !important; overflow:visible">
        <thead style="color: #ffffff !important; background-color:#0665d0; text-align:left !important;">
            <th class="invoice-td" style="text-align:left !important">Student name</th>
            <th class="invoice-td">Class</th>
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
                            @if (!$student->course)
                                -
                            @else
                                {{$student->course->class}}
                            @endif
                    </td>
                    <td class="invoice-td text-center">
                        @if ($student->pivot->expense_type == '9f713944-99c2-4317-b0d6-6b25980f6a02')
                            <div style="font-family: DejaVu Sans, sans-serif;">✔</div>
                        @endif
                    </td>
                    <td class="invoice-td text-center">
                        @if ($student->pivot->expense_type == '9f713944-9b19-4aa1-a864-f98bb6d8d7b4')
                            <div style="font-family: DejaVu Sans, sans-serif;">✔</div>
                        @endif
                    </td>
                </tr>
                @endforeach
        </tbody>
    </table>
</div>

@include('pdf_templates.partials.groupFooter')
