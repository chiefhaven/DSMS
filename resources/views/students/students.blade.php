@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Students</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
                {{Session::get('message')}}
            </div>
            @endif

            @role(['superAdmin', 'admin'])
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-sm-inline-block">Action</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0">
                        <div class="p-2">
                        @role(['superAdmin', 'admin'])
                            <a href="{{ url('/addstudent') }}" class="dropdown-item nav-main-link">
                                <i class="fa fa-fw fa-user-plus mr-1"></i>&nbsp; Add student
                            </a>
                            <button class="dropdown-item nav-main-link" data-bs-toggle="modal" data-bs-target="#modal-block-vcenter">
                                <i class="fa fa-download"></i> &nbsp; Students report
                            </button>
                        @endcan
                        </div>
                    </div>
                </div>
            @endcan
        </nav>
        </div>
    </div>
    </div>

    <div class="content content-full">
        <div class="block block-rounded block-bordered">
            <div class="m-4 table-responsive">
                <table id="students" class="table table-bordered table-striped table-vcenter">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 100px;">Actions</th>
                            <th style="min-width: 10rem;">Name</th>
                            <th style="min-width: 10rem;">Course Enrolled</th>
                            @role('superAdmin|admin')
                                <th>Balance</th>
                            @endrole
                            <th style="min-width: 10rem;">Registered on</th>
                            @role(['superAdmin','admin'])
                                <th style="min-width: 10rem;">Car assigned</th>
                            @endrole
                            <th>Attendance</th>
                            <th style="min-width: 10rem;">Course Status</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>TRN</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    @role(['superAdmin', 'admin'])
        <!-- Payment Modal -->
            <div class="modal" id="modal-block-vcenter" tabindex="-1" aria-labelledby="modal-block-vcenter" style="display: none;" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="block block-rounded block-themed block-transparent mb-0">
                        <div class="block-header bg-primary-dark">
                            <h3 class="block-title">Filter to download report</h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fa fa-fw fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="block-content">
                            <form class="mb-5" action="{{ url('/studentsPdf') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6 mb-4">
                                        <label for="invoice_discount">Date</label>
                                        <select class="form-select dropdown-toggle" id="date" name="date">
                                                <option class="" value="all_time">All Time</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-4">
                                        <label for="balance">Balance</label>
                                        <select class="form-control dropdown-toggle" id="balance" name="balance">
                                                <option class="" value="all">All</option>
                                                <option class="text-left" value="balance">With balance</option>
                                                <option class="" value="no_balance">No balance</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-4">
                                        <label for="car">Assigned Car</label>
                                        <select class="form-select" id="fleet" name="fleet">
                                            <option class="" value="alltime" selected>All</option>
                                            @foreach($fleet as $fleet_option)
                                                <option value="{{$fleet_option->car_registration_number}}">{{$fleet_option->car_registration_number}} ({{$fleet_option->car_brand_model}})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-4">
                                        <label for="status">Student Status</label>
                                        <select class="form-control dropdown-toggle form-select" id="status" name="status">
                                                <option class="" value="allstatus" selected>All</option>
                                                <option value="inprogress">In progress</option>
                                                <option value="finished">Completed</option>
                                        </select>
                                    </div>
                                    <div class="block-content block-content-full text-end bg-body">
                                        <button type="submit" class="btn btn-primary">Download</button>
                                        <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    @endcan


<script type="text/javascript">
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
            title: 'Delete Student',
            text: 'Do you want to delete this student?',
            icon: 'error',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed)
                form.submit();
        });
    });

    $(document).ready(function () {
        $('#students').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: '/api/students',
                type: 'GET',
                error: function (xhr, error, thrown) {
                    alert('Failed to load data. Please try again.');
                },
            },
            columns: [
                {
                    data: 'actions',
                    className: 'text-center',
                    orderable: false
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return `${row.fname} ${row.mname || ''} ${row.sname}`.trim();
                    },
                    className: 'text-wrap'
                },
                {
                    data: 'course_enrolled',
                    className: 'text-wrap',
                    render: function (data) {
                        return data || '-';
                    }
                },
                @if(auth()->user()->hasRole(['superAdmin', 'admin']))
                {
                    data: 'balance',
                    className: 'text-right',
                    render: function (data) {
                        return data ? `${data}` : '-';
                    }
                },
                @endif
                {
                    data: 'registered_on',
                    className: 'text-center',
                    render: function (data) {
                        return data || '-';
                    }
                },
                @if(auth()->user()->hasRole(['superAdmin', 'admin']))
                {
                    data: 'car_assigned',
                    className: 'text-center',
                    render: function (data) {
                        return data || '-';
                    }
                },
                @endif
                {
                    data: 'attendance',
                    className: 'text-center',
                    render: function (data) {
                        return data || '0';
                    }
                },
                {
                    data: 'course_status',
                    className: 'text-wrap',
                    render: function (data) {
                        return data || '-';
                    }
                },
                {
                    data: 'phone',
                    render: function (data) {
                        return data || '-';
                    }
                },
                {
                    data: 'email',
                    render: function (data) {
                        return data || '-';
                    }
                },
                {
                    data: 'trn',
                    render: function (data) {
                        return data || '-';
                    }
                },
            ],
        });
    });

</script>
<!-- END Hero -->
@endsection
