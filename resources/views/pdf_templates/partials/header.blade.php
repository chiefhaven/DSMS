<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" id="css-main" href="{{ asset('css/dashmix.css') }}"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>


<div class="watermark">
    <div id="watermark">
        <p>
            @if(isset($student->fname))
                {{ $watermak = $student->fname.' '.$student->mname.' '.$student->sname }}
                @for($i = 0; $i < 1500; $i++)
                    {{ $watermak }}
                @endfor
            @elseif(isset($invoice->student->fname))
                @for($i = 0; $i < 1500; $i++)
                    {{ $watermak }}
                @endfor
            @else
                @for($i = 0; $i < 1500; $i++)
                    Driving School Management System
                @endfor
            @endif
        </p>
    </div>
</div>

<div class="" style="height: 300%; width: 5px; position:absolute; top:-90px; left: 30px; z-index: 999; background: blue;">
    <p>&nbsp;</p>
</div>
<div class="" style="height: 300%; width: 2px; position:absolute; top:-90px; left: 35px; z-index: 999; background: black;">
    <p>&nbsp;</p>
</div>
<div class="" style="height: 300%; width: 3px; position:absolute; top:-90px; left: 37px; z-index: 999; background: blue;">
    <p>&nbsp;</p>
</div>
