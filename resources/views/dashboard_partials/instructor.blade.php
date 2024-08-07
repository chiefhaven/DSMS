<div class="row">
        <div class="col-md-6 col-xl-6">
            <h1>Welcome <b>{{ Auth::user()->instructor->fname }}</b> {{ Auth::user()->instructor->sname }}</h1>
            Assigned car: {{ Auth::user()->instructor->fleet->car_registration_number }}
        </div>

</div>

<div class="block-content">
    <div class="row">
        <div class="col-md-8 col-xl-8 card p-5 mt-6">
            {!! \Illuminate\Foundation\Inspiring::quote() !!}
        </div>
    </div>
</div>
