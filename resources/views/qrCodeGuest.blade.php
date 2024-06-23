<x-guest-layout>
<div class="block-content">
    <div class="content">
        <div class="text-uppercase">
            @if(isset($student))
                This is a valid document from Daron Driving School for {{ $student->fname }} {{ $student->mname }} {{ $student->sname }}!
            @else
                Document or url not valid!
            @endif
        </div>
        <div>
            <hr>
            For more information contact us on <br>
            Phone: +265 999 532 688 | +265 887 226 317<br>
            Email: info@darondrivingschool.com
        </div>
    </div>
</div>
</x-guest-layout>
