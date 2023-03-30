<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

@include('pdf_templates.partials.header')
@include('pdf_templates.partials.pdf_template_style')
@include('pdf_templates.partials.letter_head')


    <h3 style="text-transform: uppercase;"><u>APPLICATION FOR A ROAD TEST FOR {{$student->fname}} {{$student->mname}} {{$student->sname}}</u></h3>

    <P>Am writing to seek your assistance for {{$student->fname}} {{$student->mname}} {{$student->sname}} to take the practical road test. {{$student->fname}} {{$student->mname}} {{$student->sname}} is one of our students here at DARON DRIVING SCHOOL and has completed his driving lessons and is ready for the road test.</P>
     
@include('pdf_templates.partials.footer')