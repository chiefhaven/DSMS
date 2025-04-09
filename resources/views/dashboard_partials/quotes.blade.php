<div class="col-md-12 col-xl-12 mb-4">
    <h1>
        @php
            $admin = Auth::user()->administrator;
            $instructor = Auth::user()->instructor;
            $student = Auth::user()->student;
            $hour = now()->format('H');

            if ($hour < 12) {
                $icon = '<i class="fas fa-sun" style="margin-left: 15px;"></i>';
                $greeting = 'Good morning';
            } elseif ($hour < 18) {
                $icon = '<i class="fas fa-cloud-sun" style="margin-left: 15px;"></i>';
                $greeting = 'Good afternoon';
            } else {
                $icon = '<i class="fas fa-moon" style="margin-left: 15px;"></i>';
                $greeting = 'Good evening';
            }
        @endphp

        <span>{{ $greeting }},</span>

        @if($admin)
            <b>{{ $admin->fname ?? 'User' }}</b> {{ $admin->sname }} {!! $icon !!}
        @elseif($instructor)
            <b>{{ $instructor->fname ?? 'User' }}</b> {{ $instructor->sname }} {!! $icon !!}
        @elseif($student)
            <b>{{ $student->fname ?? 'User' }}</b> {{ $student->sname }} {!! $icon !!}
        @else
            <b>{{ Auth::user()->name ?? 'User' }}</b> {!! $icon !!}
        @endif
    </h1>

    <hr>
    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
        {!! \Illuminate\Foundation\Inspiring::quote() !!}
    </div>
</div>