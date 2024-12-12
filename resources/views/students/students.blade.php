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
            <div class="block-content">
                {{--  <div class="col-md-12 mb-1">
                    <form action="{{ url('/search-student') }}" method="GET" enctype="multipart/form-data">
                        @csrf
                            <input type="text" class="col-md-5 block block-bordered p-2" id="search" name="search" placeholder="Search student" required>
                            <button type="submit" class="p-2 btn btn-alt-primary">
                                <i class="fa fa-search opacity-50 me-1"></i> Search
                            </button>
                    </form>
                </div>  --}}
            </div>
            <div class="m-4 table-responsive">
                @if( !$student->isEmpty())
                <table id="students" class="table table-bordered table-striped table-vcenter">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 100px;">Actions</th>
                            <th>Name</th>
                            <th style="min-width: 15rem;">Course Enrolled</th>
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
                    <tbody>
                        @if($student->isNotEmpty())
                            @foreach ($student as $students)
                            @php
                                $attendancePercentage = $students->course && $students->course->duration > 0
                                    ? number_format($students->attendance->count() / $students->course->duration * 100)
                                    : 0;
                                $invoiceBalance = $students->invoice->invoice_balance ?? 0;
                            @endphp
                                <tr>
                                    <td class="text-center">
                                        <!-- Actions Dropdown -->
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-primary" data-bs-toggle="dropdown">Actions</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="{{ url('/viewstudent', $students->id) }}">
                                                    <i class="fa fa-user"></i> Profile
                                                </a>
                                                @can('edit student')
                                                    <a class="dropdown-item" href="{{ url('/edit-student', $students->id) }}">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                @endcan
                                                @can('delete student')
                                                    <form method="POST" action="{{ url('student-delete', $students->id) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item delete-confirm">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $students->fname }} {{ $students->mname }} {{ $students->sname }}</td>
                                    <td>{{ optional($students->course)->name ?? 'Not enrolled yet.' }}</td>
                                    @role(['superAdmin','admin'])
                                        <td>
                                            <strong>
                                                <span class="{{ $invoiceBalance > 0 ? 'text-danger' : 'text-success' }}">
                                                    K{{ number_format($invoiceBalance, 2) }}
                                                </span>
                                            </strong>
                                        </td>
                                    @endrole
                                    <td>{{ $students->created_at->format('j F, Y') }}</td>
                                    @role(['superAdmin','admin'])
                                        <td>
                                            {{ optional($students->fleet)->car_registration_number ?? 'Not assigned yet' }}
                                        </td>
                                    @endrole
                                    <td class="text-center">
                                        @if($attendancePercentage >= 100)
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($attendancePercentage >= 50)
                                            <span class="badge bg-info">{{ $attendancePercentage }}%</span>
                                        @else
                                            <span class="badge bg-warning">{{ $attendancePercentage }}%</span>
                                        @endif
                                    </td>
                                    <td>{{ $students->status }}</td>
                                    <td>{{ $students->phone }}</td>
                                    <td>{{ optional($students->user)->email ?? '-' }}</td>
                                    <td>{{ $students->trn }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="12" class="text-center">No students found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                    {{--  {{ $student->links('pagination::bootstrap-4') }}  --}}
                @else
                    <p class="p-5">No matching records found!</p>
                @endif
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

    $(document).ready(function() {
        $('#students').DataTable();
    });
</script>
<!-- END Hero -->
@endsection
