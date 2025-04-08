<div class="col-md-12 col-xl-12 mb-4">
    <h1>
        @php
            $admin = Auth::user()->administrator;
            $instructor = Auth::user()->instructor;
            $student = Auth::user()->student;
            $hour = now()->format('H');

            if ($hour < 12) {
                $icon = '🌞';
                $greeting = 'Good morning';
            } elseif ($hour < 18) {
                $icon = '🌤️';
                $greeting = 'Good afternoon';
            } else {
                $icon = '🌙';
                $greeting = 'Good evening';
            }
        @endphp

        <span>
            {{ $icon }} {{ $greeting }},
        </span>

        @if($admin)
            <b>{{ $admin->fname ?? 'User' }}</b> {{ $admin->sname }}
        @elseif($instructor)
            <b>{{ $instructor->fname ?? 'User' }}</b> {{ $instructor->sname }}
        @elseif($student)
            <b>{{ $student->fname ?? 'User' }}</b> {{ $student->sname }}
        @else
            <b>{{ Auth::user()->name ?? 'User' }}</b>
        @endif


    </h1>
    <hr>
    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
        {!! \Illuminate\Foundation\Inspiring::quote() !!}
    </div>
</div>