<link rel="stylesheet" id="css-main" href="{{ asset('css/dashmix.css') }}">

<div class="watermark">
    <div id="watermark">
        <p>
            @if(isset($student->fname))
                @for($i = 0; $i < 1000; $i++)
                    {{$student->fname}} {{$student->mname}} {{$student->sname}}
                @endfor
            @elseif(isset($invoice->student->fname))
                @for($i = 0; $i < 1000; $i++)
                    {{$invoice->student->fname}} {{$invoice->student->mname}} {{$invoice->student->sname}}
                @endfor
            @else
                @for($i = 0; $i < 1000; $i++)
                    Driving School Management System
                @endfor
            @endif
        </p>
    </div>
</div>

<div class="" style="height: 300%; width: 5px; position:absolute; top:-90px; left: -13.5px; z-index: 999; background: blue;">
    <p>&nbsp;</p>
</div>
<div class="" style="height: 300%; width: 2px; position:absolute; top:-90px; left: -10px; z-index: 999; background: black;">
    <p>&nbsp;</p>
</div>
<div class="" style="height: 300%; width: 3px; position:absolute; top:-90px; left: -8px; z-index: 999; background: blue;">
    <p>&nbsp;</p>
</div>