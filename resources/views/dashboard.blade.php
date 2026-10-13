@extends('layouts.backend')

@section('title', 'Dashboard')

@section('content')

    <!-- Dashboard Header -->
    <div class="bg-body-light">
        <div class="content content-full">

            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">

                <div class="flex-grow-1">
                    <h1 class="fs-3 fw-semibold my-2 my-sm-3">
                        Dashboard
                    </h1>
                </div>

                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3"
                     aria-label="breadcrumb">

                    <ol class="breadcrumb mb-0">

                        <li class="breadcrumb-item">
                            App
                        </li>

                        <li class="breadcrumb-item active"
                            aria-current="page">
                            Dashboard
                        </li>

                    </ol>

                </nav>

            </div>

        </div>
    </div>
    <!-- END Dashboard Header -->


    <!-- Dashboard Content -->
    <div class="content content-full">

        @if(auth()->user()->hasRole('superAdmin'))

            {{-- Super Administrator Dashboard --}}
            @include('dashboard_partials.super_admin')

        @elseif(auth()->user()->hasAnyRole(['admin', 'financeAdmin']))

            {{-- Administrator / Finance Administrator Dashboard --}}
            @include('dashboard_partials.admin')

        @elseif(auth()->user()->hasRole('instructor'))

            {{-- Instructor Dashboard --}}
            @include('dashboard_partials.instructor')

        @elseif(auth()->user()->hasRole('student'))

            {{-- Student Dashboard --}}
            @include('dashboard_partials.student')

        @else

            {{-- No Dashboard Role Assigned --}}
            <div class="block block-rounded">

                <div class="block-content block-content-full">

                    <div class="text-center py-5">

                        <i class="fa fa-user-lock fa-3x text-muted mb-4"></i>

                        <h3 class="fs-4 fw-semibold mb-2">
                            Dashboard Access Unavailable
                        </h3>

                        <p class="text-muted mb-0">
                            Your account does not have a dashboard role assigned.
                            Please contact the system administrator.
                        </p>

                    </div>

                </div>

            </div>

        @endif

    </div>
    <!-- END Dashboard Content -->

@endsection
