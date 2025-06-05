@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Roles</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
                {{Session::get('message')}}
            </div>
            @endif
            <div class="dropdown d-inline-block">

            </div>
        </nav>
        </div>
    </div>
    </div>

    <div class="content content-full">
        <div class="block block-rounded block-bordered">


        </div>
    </div>
<!-- END Hero -->
@endsection
