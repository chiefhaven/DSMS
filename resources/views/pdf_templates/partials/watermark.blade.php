<div class="watermark">
    <div id="watermark">
        <p>
            @if(isset($student->fname))
                {{ $watermak = $student->fname.' '.$student->mname.' '.$student->sname }}
                @for($i = 0; $i < 1500; $i++)
                    {{ $watermak }}
                @endfor
            @elseif(isset($invoice->student->fname))
                {{ $watermak = $invoice->student->fname.' '.$invoice->student->mname.' '.$invoice->student->sname }}
                @for($i = 0; $i < 1500; $i++)
                    {{$watermak}}
                @endfor
            @else
                @for($i = 0; $i < 1500; $i++)
                    Daron Driving School Management System
                @endfor
            @endif
        </p>
    </div>
</div>
