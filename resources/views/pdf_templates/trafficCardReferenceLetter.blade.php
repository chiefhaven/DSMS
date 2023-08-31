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


            <h3 style="text-transform: uppercase;"><u>REFERENCE LETTER FOR {{$student->fname}} {{$student->mname}} {{$student->sname}}</u></h3>

            <p>This letter serves as a reference for {{$student->fname}} {{$student->mname}} {{$student->sname}} to support

                @if($student->gender == "Male")
                    his
                @elseif($student->gender == "Female")
                    her
                @else
                    his/her
                @endif

                application for a Traffic Register Card. {{$student->fname}} {{$student->mname}} {{$student->sname}} is one of our students here at DARON DRIVING SCHOOL and would like to apply for a traffic register card.</p>

@include('pdf_templates.partials.footer')
