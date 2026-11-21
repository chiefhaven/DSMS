@extends('layouts.backend')

@section('title', 'Dashboard')

@section('content')
    <!-- Dashboard Content -->
    <div class="content content-full">

        @cannot('access management')
            @role('instructor')
                @include('dashboard_partials.instructor')
            @else
                @role('student')
                    @include('dashboard_partials.student')
                @endrole
            @endrole
        @endcannot

        @can('access management')
        @role('superAdmin')

            {{-- Super Administrator Dashboard --}}
            @include('dashboard_partials.super_admin')

        @else
            @role(['admin', 'financeAdmin'])

                {{-- Administrator / Finance Administrator Dashboard --}}
                @include('dashboard_partials.admin')

            @else
                @role('instructor')

                    {{-- Instructor Dashboard --}}
                    @include('dashboard_partials.instructor')

                @else
                    @role('student')

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
                    @endrole
                    @endcan
                @endrole
            @endrole
        @endrole

    </div>
    <!-- END Dashboard Content -->

@endsection
