<div class="row">
        <div class="col-md-6 col-xl-6">
            <h1>
                Welcome <b>{{ Auth::user()->instructor->fname }}</b> {{ Auth::user()->instructor->sname }}
            </h1>
            Department: {{ Auth::user()->instructor->department->name ?? '' }}
            <p>
                @if (Auth::user()->instructor->fleet)
                    Assigned car: {{ Auth::user()->instructor->fleet->car_registration_number }}
                @elseif (Auth::user()->instructor->classrooms && Auth::user()->instructor->classrooms->isNotEmpty())
                    Assigned classrooms:
                    @foreach (Auth::user()->instructor->classrooms as $classroom)
                        {{ $classroom->name }}{{ !$loop->last ? ',' : '' }}
                    @endforeach
                @else
                    Not yet assigned car or classroom
                @endif
            </p>
        </div>

</div>

<div class="block-content">
    <div class="row">
        <div class="col-md-8 col-xl-8 card p-5 mt-6">
            {!! \Illuminate\Foundation\Inspiring::quote() !!}
        </div>
    </div>
</div>
