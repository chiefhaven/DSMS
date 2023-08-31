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

    <h3 style="text-transform: uppercase;"><u>APPLICATION FOR 2nd APTITUDE TEST FOR {{$student->fname}} {{$student->mname}} {{$student->sname}}</u></h3>

    <P>Am writing to seek your assistance for {{$student->fname}} {{$student->mname}} {{$student->sname}} to take the 2nd aptitude test. {{$student->fname}} {{$student->mname}} {{$student->sname}} is one of our students here at DARON DRIVING SCHOOL and has been attending practical and theory lessons as shown in the attached attendance report.</P>

@include('pdf_templates.partials.footer')
