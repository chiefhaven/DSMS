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
@include('pdf_templates.partials.stamp')
@include('pdf_templates.partials.seal')


    <h3 style="text-transform: uppercase;"><u>APPLICATION FOR PROFESSIONAL DRIVING PERMIT APTITUDE TEST FOR {{$student->fname}} {{$student->mname}} {{$student->sname}}</u></h3>

    <p>I am writing to seek your assistance for {{$student->fname}} {{$student->mname}} {{$student->sname}} to take 
        @if($student->gender == "Male")
            his
        @elseif($student->gender == "Female")
            her
        @else
            his/her
        @endif
        
        Professional Driving Permit aptitude test for Categories Goods and Dangerous Goods.</p>
    <p>{{$student->fname}} {{$student->mname}} {{$student->sname}} is one of our students at Daron Driving School and has held a Category C1 driver’s licence for a minimum of two years, making 
        @if($student->gender == "Male")
            his
        @elseif($student->gender == "Female")
            her
        @else
            his/her
        @endif eligible to apply for the professional driving permit aptitude test.</p>

@include('pdf_templates.partials.footer')
