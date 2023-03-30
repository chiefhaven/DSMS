<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

@include('pdf_templates.partials.header')
@include('pdf_templates.partials.pdf_template_style')
@include('pdf_templates.partials.letter_head')


    <h3 style="text-transform: uppercase;"><u>APPLICATION FOR AN APTITUDE TEST FOR {{$student->fname}} {{$student->mname}} {{$student->sname}}</u></h3>

    <p>Am writing to seek your assistance for {{$student->fname}} {{$student->mname}} {{$student->sname}} to take an aptitude test. {{$student->fname}} {{$student->mname}} {{$student->sname}} is one of our students here at DARON DRIVING SCHOOL and has completed our 15 days long theory lessons which mainly focused on highway code 1 and K53.</p>
     
    @include('pdf_templates.partials.footer')